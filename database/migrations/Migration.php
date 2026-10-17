<?php

declare(strict_types=1);

abstract class Migration
{
    abstract public function up(PDO $db): void;

    abstract public function down(PDO $db): void;
}
