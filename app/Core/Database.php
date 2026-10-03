<?php

declare(strict_types=1);

namespace Assessment\Core;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::get('DB_HOST', 'db'),
            Config::get('DB_PORT', '3306'),
            Config::get('DB_DATABASE', 'assessment')
        );

        return new PDO(
            $dsn,
            Config::get('DB_USERNAME', 'assessment'),
            Config::get('DB_PASSWORD', 'assessment'),
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            )
        );
    }
}

