<?php

declare(strict_types=1);

namespace Assessment\Model;

use PDO;

final class UserModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, name, role FROM users WHERE id = :id');
        $statement->execute(array('id' => $id));
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}

