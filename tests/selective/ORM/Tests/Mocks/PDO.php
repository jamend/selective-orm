<?php
namespace selective\ORM\Tests\Mocks;

class PDO extends \PDO
{
    public $lastInsertId = 0;

    public function __construct()
    {
    }

    public function prepare($statement, $options = null)
    {
        return new PDOStatement($statement, $this);
    }

    public function lastInsertId($seqname = null)
    {
        return $this->lastInsertId;
    }
}