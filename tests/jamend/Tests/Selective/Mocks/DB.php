<?php
namespace jamend\Tests\Selective\Mocks;

class DB extends \jamend\Selective\DB\PDOMySQL
{
    protected function connect()
    {
        $this->pdo = new PDO();
    }
}