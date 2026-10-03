<?php

declare(strict_types=1);

use Assessment\Core\Database;
require dirname(__DIR__) . '/vendor/autoload.php';

$command = $argv[1] ?? 'help';
$db = Database::connect();

if ($command === 'migrate') {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS migrations (' .
        'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ' .
        'migration VARCHAR(255) NOT NULL UNIQUE, ' .
        'executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $applied = $db->query('SELECT migration FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);
    foreach (glob(dirname(__DIR__) . '/database/migrations/*.php') ?: array() as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }

        // MySQL implicitly commits DDL statements, so migrations are recorded
        // only after their callable completes successfully.
        $migration = require $file;
        $migration($db);
        $statement = $db->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $statement->execute(array('migration' => $name));
        echo "Migrated: {$name}\n";
    }
    exit(0);
}

if ($command === 'seed') {
    foreach (glob(dirname(__DIR__) . '/database/seeds/*.php') ?: array() as $file) {
        $seed = require $file;
        $seed($db);
        echo 'Seeded: ' . basename($file) . "\n";
    }
    exit(0);
}

echo "Usage:\n  php bin/console.php migrate\n  php bin/console.php seed\n";

