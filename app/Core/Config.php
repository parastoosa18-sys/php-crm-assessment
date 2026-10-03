<?php

declare(strict_types=1);

namespace Assessment\Core;

final class Config
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}

