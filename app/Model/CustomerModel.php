<?php

declare(strict_types=1);

namespace Assessment\Model;

use PDO;

final class CustomerModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function visibleTo(array $user): array
    {
        if ($user['role'] === 'admin') {
            return $this->db->query('SELECT id, name, assigned_user_id FROM customers ORDER BY id')->fetchAll();
        }

        $statement = $this->db->prepare(
            'SELECT id, name, assigned_user_id FROM customers WHERE assigned_user_id = :user_id ORDER BY id'
        );
        $statement->execute(array('user_id' => (int) $user['id']));

        return $statement->fetchAll();
    }

    public function isVisibleTo(int $customerId, array $user): bool
    {
        $sql = 'SELECT COUNT(*) FROM customers WHERE id = :id';
        $params = array('id' => $customerId);

        if ($user['role'] !== 'admin') {
            $sql .= ' AND assigned_user_id = :user_id';
            $params['user_id'] = (int) $user['id'];
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() === 1;
    }
}

