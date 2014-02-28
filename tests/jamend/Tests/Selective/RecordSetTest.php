<?php
namespace jamend\Tests\Selective;

class RecordSetTest extends \PHPUnit_Framework_TestCase {
	public function testGetRecordSet() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$this->assertNotNull($table);
		$this->assertInstanceOf('jamend\Selective\RecordSet', $table);
	}
	
	public function testOpenRecordSet() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$table2 = $table->openRecordSet();
		$this->assertNotNull($table2);
		$this->assertInstanceOf('jamend\Selective\RecordSet', $table2);
		$this->assertTrue($table !== $table2);
	}
	
	public function testGetByID() {
		$db = \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\MockDB', array());
		$table = $db->{'Book'};
		$id = 1;
		$record = $table->{$id};
		$this->assertNotSame(false, $record);
		$this->assertInstanceOf('jamend\Selective\Record', $record);
		$this->assertEquals($record->getID(), $id);
	}
}