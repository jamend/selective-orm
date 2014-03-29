<?php
namespace jamend\Selective\Tests\Mocks;

class Database extends \jamend\Selective\Database
{
    public function connect()
    {
        $this->pdo = new PDO();
    }
}