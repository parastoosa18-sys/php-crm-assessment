<?php

declare(strict_types=1);

namespace Assessment\Tests\Integration;
use CreateUsersAndCustomers;
use CreateFollowups;
use Assessment\Model\FollowupModel;
use PDO;
use PHPUnit\Framework\TestCase;

final class FollowupStatusTest extends TestCase
{
    private PDO $db;
    private FollowupModel $model;
    private array $admin;

    protected function setUp(): void
    {
        $this->db = new PDO(
            'mysql:host=db;dbname=assessment_test;charset=utf8mb4',
            'assessment',
            'assessment',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );

        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->exec('DROP TABLE IF EXISTS followup_history');
        $this->db->exec('DROP TABLE IF EXISTS followups');
        $this->db->exec('DROP TABLE IF EXISTS customers');
        $this->db->exec('DROP TABLE IF EXISTS users');
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');

       
require_once dirname(__DIR__, 2)
    . '/database/migrations/Migration.php';

require_once dirname(__DIR__, 2)
    . '/database/migrations/001_create_users_and_customers.php';

require_once dirname(__DIR__, 2)
    . '/database/migrations/002_create_followups.php';

$migration = new CreateUsersAndCustomers();
$migration->up($this->db);

$migration = new  CreateFollowups();
$migration->up($this->db);

        $this->db->exec(
            "INSERT INTO users (id, name, role)
             VALUES
                (1, 'Admin', 'admin'),
                (2, 'Agent One', 'agent'),
                (3, 'Agent Two', 'agent')"
        );

        $this->db->exec(
            "INSERT INTO customers (id, name, assigned_user_id)
             VALUES
                (1, 'Agent One Customer', 2),
                (2, 'Agent Two Customer', 3)"
        );

        $this->model = new FollowupModel($this->db);
        $this->admin = array('id' => 1, 'role' => 'admin');
    }

    protected function tearDown(): void
    {
        unset($this->db);
    }

    public function testAgentCannotAccessAnotherAgentsFollowup(): void
{
    $agentOne = array('id' => 2, 'role' => 'agent');
    $agentTwo = array('id' => 3, 'role' => 'agent');

    $id = $this->model->create(
        array(
            'customer_id' => 2,
            'title' => 'Private follow-up',
            'description' => null,
            'priority' => 'normal',
            'due_at' => '2026-11-01 10:00:00'
        ),
        $this->admin
    );

    self::assertFalse(
        $this->model->changeStatus($id, 'completed', $agentOne)
    );

    self::assertNull(
        $this->model->findVisible($id, $agentOne)
    );

    self::assertNotNull(
        $this->model->findVisible($id, $agentTwo)
    );
}

public function testListVisibleFiltersByStatus(): void
{
    $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'Open follow-up',
            'description' => null,
            'priority' => 'normal',
            'due_at' => '2026-11-01 10:00:00'
        ),
        $this->admin
    );

    $completedId = $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'Completed follow-up',
            'description' => null,
            'priority' => 'high',
            'due_at' => '2026-11-02 10:00:00'
        ),
        $this->admin
    );

    $this->model->changeStatus(
        $completedId,
        'completed',
        $this->admin
    );

    $result = $this->model->listVisible(
        $this->admin,
        array('status' => 'completed'),
        20,
        0
    );

    self::assertSame(1, $result['pagination']['total']);
    self::assertSame(
        'Completed follow-up',
        $result['data'][0]['title']
    );
}

public function testListVisibleFiltersByDueDateRange(): void
{
    $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'Earlier follow-up',
            'description' => null,
            'priority' => 'normal',
            'due_at' => '2026-10-20 10:00:00'
        ),
        $this->admin
    );

    $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'Follow-up in range',
            'description' => null,
            'priority' => 'normal',
            'due_at' => '2026-11-01 10:00:00'
        ),
        $this->admin
    );

    $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'Later follow-up',
            'description' => null,
            'priority' => 'normal',
            'due_at' => '2026-12-01 10:00:00'
        ),
        $this->admin
    );

    $result = $this->model->listVisible(
        $this->admin,
        array(
            'due_from' => '2026-10-25 00:00:00',
            'due_to' => '2026-11-30 23:59:59'
        ),
        20,
        0
    );

    self::assertSame(1, $result['pagination']['total']);
    self::assertSame(
        'Follow-up in range',
        $result['data'][0]['title']
    );
}


    public function testCompleteAndReopenRecordsStatusHistory(): void
    {
        $id = $this->model->create(
            array(
                'customer_id' => 1,
                'title' => 'Test follow-up',
                'description' => null,
                'priority' => 'normal',
                'due_at' => '2026-11-01 10:00:00'
            ),
            $this->admin
        );

        self::assertTrue(
            $this->model->changeStatus($id, 'completed', $this->admin)
        );

        $completed = $this->model->findVisible($id, $this->admin);

        self::assertSame('completed', $completed['status']);
        self::assertNotNull($completed['completed_at']);

        self::assertTrue(
            $this->model->changeStatus($id, 'open', $this->admin)
        );

        $reopened = $this->model->findVisible($id, $this->admin);

        self::assertSame('open', $reopened['status']);
        self::assertNull($reopened['completed_at']);
        self::assertCount(3, $reopened['history']);

        self::assertSame('open', $reopened['history'][0]['new_status']);
        self::assertSame('completed', $reopened['history'][1]['new_status']);
        self::assertSame('open', $reopened['history'][2]['new_status']);
    }

public function testListVisiblePaginatesResults(): void
{
    for ($i = 1; $i <= 3; $i++) {
        $this->model->create(
            array(
                'customer_id' => 1,
                'title' => 'Follow-up ' . $i,
                'description' => null,
                'priority' => 'normal',
                'due_at' => sprintf(
                    '2026-11-%02d 10:00:00',
                    $i
                )
            ),
            $this->admin
        );
    }

    $result = $this->model->listVisible(
        $this->admin,
        array(),
        2,
        0
    );

    self::assertSame(3, $result['pagination']['total']);
    self::assertSame(2, $result['pagination']['limit']);
    self::assertSame(0, $result['pagination']['offset']);
    self::assertCount(2, $result['data']);

    $nextPage = $this->model->listVisible(
        $this->admin,
        array(),
        2,
        2
    );

    self::assertCount(1, $nextPage['data']);
    self::assertSame(2, $nextPage['pagination']['offset']);
}

public function testListVisibleFiltersByPriority(): void
{
    $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'Low priority follow-up',
            'description' => null,
            'priority' => 'low',
            'due_at' => '2026-11-01 10:00:00'
        ),
        $this->admin
    );

    $this->model->create(
        array(
            'customer_id' => 1,
            'title' => 'High priority follow-up',
            'description' => null,
            'priority' => 'high',
            'due_at' => '2026-11-02 10:00:00'
        ),
        $this->admin
    );

    $result = $this->model->listVisible(
        $this->admin,
        array('priority' => 'high'),
        20,
        0
    );

    self::assertSame(1, $result['pagination']['total']);
    self::assertSame(
        'High priority follow-up',
        $result['data'][0]['title']
    );
}


public function testAdminCanAccessFollowupsOfDifferentAgents(): void
{
    $agentTwo = array(
        'id' => 3,
        'role' => 'agent'
    );

    $id = $this->model->create(
        array(
            'customer_id' => 2,
            'title' => 'Agent Two follow-up',
            'description' => null,
            'priority' => 'normal',
            'due_at' => '2026-11-01 10:00:00'
        ),
        $agentTwo
    );

    $followup = $this->model->findVisible(
        $id,
        $this->admin
    );

    self::assertNotNull($followup);
    self::assertSame(
        'Agent Two follow-up',
        $followup['title']
    );
}

}
