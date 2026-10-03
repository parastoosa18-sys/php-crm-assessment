<?php

declare(strict_types=1);

namespace Assessment\Service;

use Assessment\Core\HttpException;
use Assessment\Model\CustomerModel;

final class CustomerService
{
    private CustomerModel $customers;

    public function __construct(CustomerModel $customers)
    {
        $this->customers = $customers;
    }

    public function listVisible(array $user): array
    {
        return $this->customers->visibleTo($user);
    }

    public function assertVisible(int $customerId, array $user): void
    {
        if (!$this->customers->isVisibleTo($customerId, $user)) {
            throw new HttpException(404, 'Customer not found.');
        }
    }
}

