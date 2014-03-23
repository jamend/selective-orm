<?php
namespace jamend\Tests\Selective;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getDB()
    {
        return new \jamend\Selective\Database('test', '\jamend\Tests\Selective\Mocks\Driver', array());
    }
}