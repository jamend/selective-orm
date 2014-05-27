<?php
namespace jamend\Selective\Tests;

use jamend\Selective\Database;

/*
 * TODO
 * sets/enums
 * prefixes
 * class mapper
 * MySQL
 * Sqlsrv
 */
trait TestHelper
{
    protected function getDB()
    {
        return new Database('test', '\jamend\Selective\Tests\Mocks\Driver', array());
    }
}