<?php
namespace jamend\Tests\Selective;

class TableTest extends TestCase
{
    public function testGetTable()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertNotNull($table);
        $this->assertInstanceOf('jamend\Selective\Table', $table);
    }

    public function testGetName()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertEquals($table->getName(), 'Books');
    }

    public function testGetKeys()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $keys = $table->getKeys();
        $this->assertSame($keys, array('idBook'));
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

    public function testCreate()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $record = $table->create();
        $this->assertNotNull($record);
        $this->assertInstanceOf('jamend\Selective\Record', $record);
    }
}