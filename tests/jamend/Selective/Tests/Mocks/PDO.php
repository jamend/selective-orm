<?php
namespace jamend\Selective\Tests\Mocks;

class PDO extends \PDO
{
    public function __construct()
    {
    }

    public function prepare($statement, $options = null)
    {
        return new PDOStatement($statement);
    }
}