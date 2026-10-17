<?php

declare(strict_types=1);

namespace Assessment\Model;

use PDO;
use RuntimeException;

final class FollowupModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function visibilityCondition(array $user, string $alias = 'f'): array
    {
        if ($user['role'] === 'admin') {
            return array('', array());
        }

        return array(
            ' AND EXISTS (
                SELECT 1
                FROM customers visible_customer
                WHERE visible_customer.id = ' . $alias . '.customer_id
                  AND visible_customer.assigned_user_id = :visible_user_id
            )',
            array('visible_user_id' => (int) $user['id'])
        );
    }

    public function listVisible(
        array $user,
        array $filters,
        int $limit,
        int $offset
    ): array {
        list($visibilitySql, $visibilityParams) =
            $this->visibilityCondition($user);

        $conditions = array();
        $params = $visibilityParams;

        if (isset($filters['customer_id'])) {
            $conditions[] = 'f.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }

        if (isset($filters['status'])) {
            $conditions[] = 'f.status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['priority'])) {
            $conditions[] = 'f.priority = :priority';
            $params['priority'] = $filters['priority'];
        }

        if (isset($filters['due_from'])) {
            $conditions[] = 'f.due_at >= :due_from';
            $params['due_from'] = $filters['due_from'];
        }

        if (isset($filters['due_to'])) {
            $conditions[] = 'f.due_at <= :due_to';
            $params['due_to'] = $filters['due_to'];
        }

        $where = $conditions
            ? ' WHERE ' . implode(' AND ', $conditions)
            : ' WHERE 1 = 1';

        $countSql = 'SELECT COUNT(*)
            FROM followups f
            WHERE 1 = 1'
            . $visibilitySql
            . ($conditions ? ' AND ' . implode(' AND ', $conditions) : '');

        $countStatement = $this->db->prepare($countSql);
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $sql = 'SELECT f.*,
                       c.name AS customer_name,
                       u.name AS creator_name
                FROM followups f
                INNER JOIN customers c ON c.id = f.customer_id
                INNER JOIN users u ON u.id = f.created_by'
            . $where
            . $visibilitySql
            . ' ORDER BY f.due_at ASC, f.id DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }

        $statement->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return array(
            'data' => $statement->fetchAll(),
       'pagination' => array(
    'total' => $total,
    'limit' => max(1, min(100, $limit)),
    'offset' => max(0, $offset)));
    }

    public function findVisible(int $id, array $user): ?array
    {
        list($visibilitySql, $params) =
            $this->visibilityCondition($user);

        $sql = 'SELECT f.*,
                       c.name AS customer_name,
                       u.name AS creator_name
                FROM followups f
                INNER JOIN customers c ON c.id = f.customer_id
                INNER JOIN users u ON u.id = f.created_by
                WHERE f.id = :id'
            . $visibilitySql
            . ' LIMIT 1';

        $params['id'] = $id;

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $followup = $statement->fetch();

        if (!$followup) {
            return null;
        }

        $historyStatement = $this->db->prepare(
            'SELECT h.id, h.changed_by, u.name AS changed_by_name,
                    h.old_status, h.new_status, h.changed_at
             FROM followup_history h
             INNER JOIN users u ON u.id = h.changed_by
             WHERE h.followup_id = :followup_id
             ORDER BY h.changed_at ASC, h.id ASC'
        );

        $historyStatement->execute(array('followup_id' => $id));
        $followup['history'] = $historyStatement->fetchAll();

        return $followup;
    }

    public function create(array $data, array $user): int
    {
        list($visibilitySql, $visibilityParams) =
            $this->visibilityCondition($user, 'f');

        $customerSql = 'SELECT id FROM customers WHERE id = :customer_id';

        $customerParams = array(
            'customer_id' => (int) $data['customer_id']
        );

        if ($user['role'] !== 'admin') {
            $customerSql .= ' AND assigned_user_id = :user_id';
            $customerParams['user_id'] = (int) $user['id'];
        }

        $customerStatement = $this->db->prepare($customerSql);
        $customerStatement->execute($customerParams);

        if (!$customerStatement->fetch()) {
            throw new RuntimeException('Customer not found.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                'INSERT INTO followups
                    (customer_id, created_by, title, description,
                     priority, status, due_at)
                 VALUES
                    (:customer_id, :created_by, :title, :description,
                     :priority, :status, :due_at)'
            );

            $statement->execute(array(
                'customer_id' => (int) $data['customer_id'],
                'created_by' => (int) $user['id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'status' => 'open',
                'due_at' => $data['due_at']
            ));

            $id = (int) $this->db->lastInsertId();

            $history = $this->db->prepare(
                'INSERT INTO followup_history
                    (followup_id, changed_by, old_status, new_status)
                 VALUES (:followup_id, :changed_by, NULL, :new_status)'
            );

            $history->execute(array(
                'followup_id' => $id,
                'changed_by' => (int) $user['id'],
                'new_status' => 'open'
            ));

            $this->db->commit();

            return $id;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }
    
public function updateOpen(int $id, array $data, array $user): bool
{
    list($visibilitySql, $params) =
        $this->visibilityCondition($user);

    $sql = 'UPDATE followups AS f
            SET title = :title,
                description = :description,
                priority = :priority,
                due_at = :due_at
            WHERE f.id = :id
              AND f.status = :open_status'
        . $visibilitySql;

    $params['title'] = $data['title'];
    $params['description'] = $data['description'] ?? null;
    $params['priority'] = $data['priority'];
    $params['due_at'] = $data['due_at'];
    $params['id'] = $id;
    $params['open_status'] = 'open';

    $statement = $this->db->prepare($sql);
    $statement->execute($params);

    if ($statement->rowCount() > 0) {
        return true;
    }

    // اگر اطلاعات تغییری نکرده باشند، بررسی می‌کنیم
    // که پیگیری همچنان وجود دارد، باز است و قابل مشاهده است.
    $checkSql = 'SELECT f.id
                 FROM followups f
                 WHERE f.id = :id
                   AND f.status = :open_status'
        . $visibilitySql;

    $checkParams = $params;
    unset(
        $checkParams['title'],
        $checkParams['description'],
        $checkParams['priority'],
        $checkParams['due_at']
    );

    $check = $this->db->prepare($checkSql);
    $check->execute($checkParams);

    return $check->fetchColumn() !== false;
}


    public function changeStatus(
        int $id,
        string $newStatus,
        array $user
    ): bool {
        if (!in_array($newStatus, array('open', 'completed'), true)) {
            throw new RuntimeException('Invalid follow-up status.');
        }

        $this->db->beginTransaction();

        try {
            list($visibilitySql, $params) =
                $this->visibilityCondition($user);

            $sql = 'SELECT f.id, f.status
                    FROM followups f
                    WHERE f.id = :id'
                . $visibilitySql
                . ' FOR UPDATE';

            $params['id'] = $id;

            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            $followup = $statement->fetch();

            if (!$followup) {
                $this->db->rollBack();
                return false;
            }

            $oldStatus = $followup['status'];

            if ($oldStatus === $newStatus) {
                $this->db->commit();
                return true;
            }

            $update = $this->db->prepare(
                'UPDATE followups
                 SET status = :status,
                     completed_at = :completed_at
                 WHERE id = :id'
            );

            $update->execute(array(
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completed'
                    ? date('Y-m-d H:i:s')
                    : null,
                'id' => $id
            ));

            $history = $this->db->prepare(
                'INSERT INTO followup_history
                    (followup_id, changed_by, old_status, new_status)
                 VALUES
                    (:followup_id, :changed_by, :old_status, :new_status)'
            );

            $history->execute(array(
                'followup_id' => $id,
                'changed_by' => (int) $user['id'],
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ));

            $this->db->commit();

            return true;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }
}