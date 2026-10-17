<?php

declare(strict_types=1);

require_once __DIR__ . '/Migration.php';

final class CreateUsersAndCustomers extends Migration
{
    public function up(PDO $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'agent') NOT NULL DEFAULT 'agent',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS customers (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                assigned_user_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_customers_assigned_user (assigned_user_id),
                CONSTRAINT fk_customers_assigned_user
                    FOREIGN KEY (assigned_user_id) REFERENCES users(id)
                    ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS customers');
        $db->exec('DROP TABLE IF EXISTS users');
    }
}
