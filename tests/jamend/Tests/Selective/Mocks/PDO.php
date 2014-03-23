<?php
namespace jamend\Tests\Selective\Mocks;

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