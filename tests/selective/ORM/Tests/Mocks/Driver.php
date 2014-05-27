<?php
namespace selective\ORM\Tests\Mocks;

class Driver extends \selective\ORM\Driver\PDO\MySQL
{
    public function loadParameters($parameters)
    {
    }

    public function connect(\selective\ORM\Database $database)
    {
        $this->pdo = new PDO();
    }
}