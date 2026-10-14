<?php

declare(strict_types=1);

return static function (\PDO $db): void {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS followups (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id INT UNSIGNED NOT NULL,
            created_by INT UNSIGNED NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NULL,
            priority ENUM('low', 'normal', 'high') NOT NULL DEFAULT 'normal',
            status ENUM('open', 'completed') NOT NULL DEFAULT 'open',
            due_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_followups_customer (customer_id),
            KEY idx_followups_creator (created_by),
            KEY idx_followups_status_due (status, due_at),
            KEY idx_followups_priority (priority),
            CONSTRAINT fk_followups_customer
                FOREIGN KEY (customer_id) REFERENCES customers(id)
                ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_followups_creator
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS followup_history (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            followup_id INT UNSIGNED NOT NULL,
            changed_by INT UNSIGNED NOT NULL,
            old_status ENUM('open', 'completed') NULL,
            new_status ENUM('open', 'completed') NOT NULL,
            changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_followup_history_followup (followup_id, changed_at),
            CONSTRAINT fk_followup_history_followup
                FOREIGN KEY (followup_id) REFERENCES followups(id)
                ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT fk_followup_history_user
                FOREIGN KEY (changed_by) REFERENCES users(id)
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};