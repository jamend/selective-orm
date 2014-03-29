<?php
namespace jamend\Selective\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getDB()
    {
        return new \jamend\Selective\Database('test', '\jamend\Selective\Tests\Mocks\Driver', array());
    }
}