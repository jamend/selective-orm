<?php
namespace jamend\Tests\Selective\Mocks;

class Driver extends \jamend\Selective\Driver\PDO\MySQL
{
    public function loadParameters($parameters)
    {
    }

    public function connect(\jamend\Selective\Database $database)
    {
        $this->pdo = new PDO();
    }
}