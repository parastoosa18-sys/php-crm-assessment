<?php

declare(strict_types=1);

namespace Assessment\Tests\Unit\Service;

use Assessment\Core\HttpException;
use Assessment\Model\FollowupModel;
use Assessment\Service\FollowupService;
use PDO;
use PHPUnit\Framework\TestCase;

final class FollowupServiceTest extends TestCase
{
    private function createService(): FollowupService
    {
        $pdo = new PDO('sqlite::memory:');
        $model = new FollowupModel($pdo);

        return new FollowupService($model);
    }

    public function testRejectsEmptyTitle(): void
    {
        $service = $this->createService();

        try {
            $service->create(array(
                'customer_id' => 1,
                'title' => '   ',
                'due_at' => '2026-11-01 10:00:00'
            ), array('id' => 1, 'role' => 'admin'));

            self::fail('Expected an HTTP 422 exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->status());
            self::assertSame('Title is required.', $exception->getMessage());
        }
    }

    public function testRejectsInvalidDueDate(): void
    {
        $service = $this->createService();

        try {
            $service->create(array(
                'customer_id' => 1,
                'title' => 'Call customer',
                'due_at' => 'not-a-date'
            ), array('id' => 1, 'role' => 'admin'));

            self::fail('Expected an HTTP 422 exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->status());
            self::assertStringContainsString(
                'due_at',
                $exception->getMessage()
            );
        }
    }

    public function testRejectsInvalidPriority(): void
    {
        $service = $this->createService();

        try {
            $service->create(array(
                'customer_id' => 1,
                'title' => 'Call customer',
                'priority' => 'urgent',
                'due_at' => '2026-11-01 10:00:00'
            ), array('id' => 1, 'role' => 'admin')); 
            self::fail('Expected an HTTP 422 exception.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->status());
            self::assertStringContainsString(
                'Priority',
                $exception->getMessage()
            );
        }
    }

public function testRejectsInvalidStatusFilter(): void
{
    $service = $this->createService();

    try {
        $service->list(
            array('id' => 1, 'role' => 'admin'),
            array('status' => 'pending')
        );

        self::fail('Expected an HTTP 422 exception.');
    } catch (HttpException $exception) {
        self::assertSame(422, $exception->status());
    }
}

public function testRejectsReversedDueDateRange(): void
{
    $service = $this->createService();

    try {
        $service->list(
            array('id' => 1, 'role' => 'admin'),
            array(
                'due_from' => '2026-11-10 10:00:00',
                'due_to' => '2026-11-01 10:00:00'
            )
        );

        self::fail('Expected an HTTP 422 exception.');
    } catch (HttpException $exception) {
        self::assertSame(422, $exception->status());
    }
}

public function testRejectsLimitAboveMaximum(): void
{
    $service = $this->createService();

    try {
        $service->list(
            array('id' => 1, 'role' => 'admin'),
            array('limit' => '101')
        );

        self::fail('Expected an HTTP 422 exception.');
    } catch (HttpException $exception) {
        self::assertSame(422, $exception->status());
    }
}

public function testRejectsNegativeOffset(): void
{
    $service = $this->createService();

    try {
        $service->list(
            array('id' => 1, 'role' => 'admin'),
            array('offset' => '-1')
        );

        self::fail('Expected an HTTP 422 exception.');
    } catch (HttpException $exception) {
        self::assertSame(422, $exception->status());
    }
}


}
