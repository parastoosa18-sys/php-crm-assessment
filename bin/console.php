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

    require_once dirname(__DIR__) . '/database/migrations/Migration.php';

    $migrationClasses = array(
        '001_create_users_and_customers.php' => 'CreateUsersAndCustomers',
        '002_create_followups.php' => 'CreateFollowups',
    );

    $applied = $db->query(
        'SELECT migration FROM migrations'
    )->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($migrationClasses as $fileName => $className) {
        if (in_array($fileName, $applied, true)) {
            continue;
        }

        $file = dirname(__DIR__) . '/database/migrations/' . $fileName;

        if (!is_file($file)) {
            throw new RuntimeException(
                'Migration file not found: ' . $fileName
            );
        }

        require_once $file;

        if (!class_exists($className)) {
            throw new RuntimeException(
                'Migration class not found: ' . $className
            );
        }

        $migration = new $className();
        $migration->up($db);

        $statement = $db->prepare(
            'INSERT INTO migrations (migration) VALUES (:migration)'
        );

        $statement->execute(array(
            'migration' => $fileName,
        ));

        echo "Migrated: {$fileName}\n";
    }

    exit(0);
}


if ($command === 'seed') {
    require_once dirname(__DIR__) . '/database/seeds/Seeder.php';

    $seeders = array(
        '001_demo_data.php' => 'DemoDataSeeder',
    );

    foreach ($seeders as $fileName => $className) {
        $file = dirname(__DIR__) . '/database/seeds/' . $fileName;

        if (!is_file($file)) {
            throw new RuntimeException(
                'Seeder file not found: ' . $fileName
            );
        }

        require_once $file;

        if (!class_exists($className)) {
            throw new RuntimeException(
                'Seeder class not found: ' . $className
            );
        }

        $seeder = new $className();
        $seeder->run($db);

        echo 'Seeded: ' . $fileName . "\n";
    }

    exit(0);
}


echo "Usage:\n  php bin/console.php migrate\n  php bin/console.php seed\n";
