<?php
namespace jamend\Selective\Tests;

class TableTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testGetTable()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertNotNull($table);
        $this->assertInstanceOf('jamend\Selective\Table', $table);
    }

    public function testBaseIdentifier()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertEquals($table->getBaseIdentifier(), '`Books`');
    }

    public function testGetName()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertEquals($table->getName(), 'Books');
    }

    public function testGetPrimaryKeys()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $primaryKeys = $table->getPrimaryKeys();
        $this->assertSame($primaryKeys, array('idBook'));
    }

    public function testGetTableColumns()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $columns = $table->getColumns();
        $this->assertArrayHasKey('idBook', $columns);
        $this->assertArrayHasKey('title', $columns);
        $this->assertArrayHasKey('idAuthor', $columns);
        $this->assertArrayHasKey('isbn', $columns);
        $this->assertArrayHasKey('description', $columns);
    }

    public function testGetByID()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $id = 1;
        $record = $table->getRecordByID($id);
        $this->assertNotSame(null, $record);
        $this->assertInstanceOf('jamend\Selective\Record', $record);
        $this->assertEquals($record->getID(), $id);
    }

    public function testMagicIsset()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};

        $id = 1;
        $this->assertTrue(isset($table->{$id}));

        $id = 3;
        $this->assertFalse(isset($table->{$id}));
    }

    public function testMagicGet()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $id = 1;
        $record = $table->{$id};
        $this->assertNotSame(false, $record);
        $this->assertInstanceOf('jamend\Selective\Record', $record);
        $this->assertEquals($record->getID(), $id);
    }

    public function testGetNonExistingRecord()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $id = 3;
        $record = $table->getRecordByID($id);
        $this->assertEquals(null, $record);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     */
    public function testMagicGetNonExistingRecord()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $id = 3;
        $record = $table->{$id};
        $this->assertEquals(null, $record);
    }

    public function testCreate()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $record = $table->create();
        $this->assertNotNull($record);
        $this->assertNull($record->getId());
        $this->assertInstanceOf('jamend\Selective\Record', $record);
    }
}