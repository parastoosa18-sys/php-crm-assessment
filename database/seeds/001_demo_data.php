<?php

declare(strict_types=1);

return static function (\PDO $db): void {
    $db->exec('DELETE FROM customers');
    $db->exec('DELETE FROM users');

    $users = $db->prepare('INSERT INTO users (id, name, role) VALUES (:id, :name, :role)');
    foreach (array(
        array(1, 'Assessment Admin', 'admin'),
        array(2, 'Sales Agent One', 'agent'),
        array(3, 'Sales Agent Two', 'agent'),
    ) as $user) {
        $users->execute(array('id' => $user[0], 'name' => $user[1], 'role' => $user[2]));
    }

    $customers = $db->prepare(
        'INSERT INTO customers (id, name, assigned_user_id) VALUES (:id, :name, :assigned_user_id)'
    );
    for ($id = 1; $id <= 10; $id++) {
        $customers->execute(array(
            'id' => $id,
            'name' => 'Demo Customer ' . $id,
            'assigned_user_id' => $id <= 5 ? 2 : 3,
        ));
    }
};

