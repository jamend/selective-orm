<?php
namespace jamend\Tests\Selective;

class TableTest extends \PHPUnit_Framework_TestCase {
	public function testGetTable() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$this->assertNotNull($table);
		$this->assertInstanceOf('jamend\Selective\Table', $table);
	}
	
	public function testGetName() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$this->assertEquals($table->getName(), 'Book');
	}
	
	public function testGetFullName() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$this->assertEquals($table->getFullName(), '`test`.`Book`');
	}
	
	public function testGetKeys() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$keys = $table->getKeys();
		$this->assertSame($keys, array('idBook'));
	}
	
	public function testGetTableColumns() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$columns = $table->getColumns();
		$this->assertArrayHasKey('idBook', $columns);
		$this->assertArrayHasKey('title', $columns);
		$this->assertArrayHasKey('idAuthor', $columns);
		$this->assertArrayHasKey('isbn', $columns);
		$this->assertArrayHasKey('description', $columns);
	}
	
	public function testCreate() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$record = $table->create();
		$this->assertNotNull($record);
		$this->assertInstanceOf('jamend\Selective\Record', $record);
	}
}