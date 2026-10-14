<?php

declare(strict_types=1);

namespace Assessment\Service;

use Assessment\Core\HttpException;
use Assessment\Model\FollowupModel;

final class FollowupService
{
    private FollowupModel $followups;

    public function __construct(FollowupModel $followups)
    {
        $this->followups = $followups;
    }

    public function list(array $user, array $query): array
    {
        $filters = array();

        if (isset($query['customer_id']) && $query['customer_id'] !== '') {
            $filters['customer_id'] = $this->positiveInteger(
                $query['customer_id'],
                'customer_id'
            );
        }

        if (isset($query['status']) && $query['status'] !== '') {
            if (!in_array($query['status'], array('open', 'completed'), true)) {
                throw new HttpException(422, 'Status must be open or completed.');
            }

            $filters['status'] = $query['status'];
        }

        if (isset($query['priority']) && $query['priority'] !== '') {
            if (!in_array($query['priority'], array('low', 'normal', 'high'), true)) {
                throw new HttpException(422, 'Priority must be low, normal, or high.');
            }

            $filters['priority'] = $query['priority'];
        }

        if (isset($query['due_from']) && $query['due_from'] !== '') {
            $filters['due_from'] = $this->dateTime(
                $query['due_from'],
                'due_from'
            );
        }

        if (isset($query['due_to']) && $query['due_to'] !== '') {
            $filters['due_to'] = $this->dateTime(
                $query['due_to'],
                'due_to'
            );
        }

        if (
            isset($filters['due_from'], $filters['due_to'])
            && $filters['due_from'] > $filters['due_to']
        ) {
            throw new HttpException(422, 'due_from must not be later than due_to.');
        }

        $limit = isset($query['limit'])
            ? $this->positiveInteger($query['limit'], 'limit')
            : 20;

        if ($limit > 100) {
            throw new HttpException(422, 'Limit cannot exceed 100.');
        }

        $offset = isset($query['offset'])
            ? filter_var($query['offset'], FILTER_VALIDATE_INT)
            : 0;

        if ($offset === false || $offset < 0) {
            throw new HttpException(422, 'Offset must be a non-negative integer.');
        }

        return $this->followups->listVisible(
            $user,
            $filters,
            $limit,
            $offset
        );
    }

    public function get(int $id, array $user): array
    {
        $followup = $this->followups->findVisible($id, $user);

        if ($followup === null) {
            throw new HttpException(404, 'Follow-up not found.');
        }

        return $followup;
    }

    public function create(array $body, array $user): array
    {
        $data = $this->validatedData($body);

        try {
            $id = $this->followups->create($data, $user);
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'Customer not found.') {
                throw new HttpException(404, 'Customer not found.');
            }

            throw $exception;
        }

        return $this->get($id, $user);
    }

public function update(int $id, array $body, array $user): array
{
    $current = $this->get($id, $user);

    if ($current['status'] !== 'open') {
        throw new HttpException(409, 'Only open follow-ups can be edited.');
    }

    if (!array_key_exists('customer_id', $body)) {
        $body['customer_id'] = (int) $current['customer_id'];
    } elseif ((int) $body['customer_id'] !== (int) $current['customer_id']) {
        throw new HttpException(422, 'Changing the customer is not allowed.');
    }

    $data = $this->validatedData($body);

    if (!$this->model->updateOpen($id, $data, $user)) {
        $latest = $this->get($id, $user);

        if ($latest['status'] !== 'open') {
            throw new HttpException(409, 'Only open follow-ups can be edited.');
        }

        throw new HttpException(404, 'Follow-up not found.');
    }

    return $this->get($id, $user);
}


    public function complete(int $id, array $user): array
    {
        if (!$this->followups->changeStatus($id, 'completed', $user)) {
            throw new HttpException(404, 'Follow-up not found.');
        }

        return $this->get($id, $user);
    }

    public function reopen(int $id, array $user): array
    {
        if (!$this->followups->changeStatus($id, 'open', $user)) {
            throw new HttpException(404, 'Follow-up not found.');
        }

        return $this->get($id, $user);
    }

    private function validatedData(array $body): array
    {
        $title = trim((string) ($body['title'] ?? ''));

        if ($title === '') {
            throw new HttpException(422, 'Title is required.');
        }

        if (mb_strlen($title) > 200) {
            throw new HttpException(422, 'Title cannot exceed 200 characters.');
        }

        $customerId = $this->positiveInteger(
            $body['customer_id'] ?? null,
            'customer_id'
        );

        $priority = $body['priority'] ?? 'normal';

        if (!is_string($priority)
            || !in_array($priority, array('low', 'normal', 'high'), true)
        ) {
            throw new HttpException(422, 'Priority must be low, normal, or high.');
        }

        $description = $body['description'] ?? null;

        if ($description !== null && !is_string($description)) {
            throw new HttpException(422, 'Description must be a string or null.');
        }

        return array(
            'customer_id' => $customerId,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'due_at' => $this->dateTime($body['due_at'] ?? null, 'due_at')
        );
    }

    private function positiveInteger($value, string $field): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false || $number <= 0) {
            throw new HttpException(
                422,
                $field . ' must be a positive integer.'
            );
        }

        return (int) $number;
    }

    private function dateTime($value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new HttpException(
                422,
                $field . ' must be a valid date and time.'
            );
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $value
        );

        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false
                && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d H:i:s') !== $value
        ) {
            throw new HttpException(
                422,
                $field . ' must use YYYY-MM-DD HH:MM:SS format.'
            );
        }

        return $date->format('Y-m-d H:i:s');
    }
}
