<?php

declare(strict_types=1);

abstract class Seeder
{
    abstract public function run(PDO $db): void;
}
