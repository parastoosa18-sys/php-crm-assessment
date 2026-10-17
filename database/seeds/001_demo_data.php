<?php

declare(strict_types=1);

require_once __DIR__ . '/Seeder.php';

final class DemoDataSeeder extends Seeder
{
    public function run(PDO $db): void
    {
        $users = array(
            array(1, 'Assessment Admin', 'admin'),
            array(2, 'Sales Agent One', 'agent'),
            array(3, 'Sales Agent Two', 'agent'),
        );

        $findUser = $db->prepare(
            'SELECT id FROM users WHERE id = :id'
        );

        $insertUser = $db->prepare(
            'INSERT INTO users (id, name, role)
             VALUES (:id, :name, :role)'
        );

        foreach ($users as $user) {
            $findUser->execute(array('id' => $user[0]));

            if ($findUser->fetchColumn() !== false) {
                continue;
            }

            $insertUser->execute(array(
                'id' => $user[0],
                'name' => $user[1],
                'role' => $user[2],
            ));
        }

        $findCustomer = $db->prepare(
            'SELECT id FROM customers WHERE id = :id'
        );

        $insertCustomer = $db->prepare(
            'INSERT INTO customers (id, name, assigned_user_id)
             VALUES (:id, :name, :assigned_user_id)'
        );

        for ($id = 1; $id <= 10; $id++) {
            $findCustomer->execute(array('id' => $id));

            if ($findCustomer->fetchColumn() !== false) {
                continue;
            }

            $insertCustomer->execute(array(
                'id' => $id,
                'name' => 'Demo Customer ' . $id,
                'assigned_user_id' => $id <= 5 ? 2 : 3,
            ));
        }
    }
}
