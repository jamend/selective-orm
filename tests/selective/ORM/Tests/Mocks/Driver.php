<?php
namespace selective\ORM\Tests\Mocks;

use selective\ORM\Database;
use selective\ORM\Driver\MySQL;

class Driver extends MySQL
{
    public function loadParameters($parameters)
    {
    }

    public function connect(Database $database)
    {
        $this->pdo = new PDO();
    }
}