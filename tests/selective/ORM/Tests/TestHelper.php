<?php
namespace selective\ORM\Tests;

use selective\ORM\Database;

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
        return new Database('test', '\selective\ORM\Tests\Mocks\Driver', [], [
            'class' => 'BuiltIn'
        ]);
    }
}