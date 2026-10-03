<?php

declare(strict_types=1);

namespace Assessment\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    private int $httpStatus;

    public function __construct(int $status, string $message)
    {
        parent::__construct($message);
        $this->httpStatus = $status;
    }

    public function status(): int
    {
        return $this->httpStatus;
    }
}

