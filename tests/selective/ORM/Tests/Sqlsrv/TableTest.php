<?php
namespace selective\ORM\Tests\Sqlsrv;

class TableTest extends \selective\ORM\Tests\TableTest
{
    use TestHelper;

    public function testBaseIdentifier()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertEquals($table->getBaseIdentifier(), '[Books]');
    }
}