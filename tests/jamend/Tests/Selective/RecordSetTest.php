<?php
namespace jamend\Tests\Selective;

class RecordSetTest extends TestCase {
	public function testGetRecordSet() {
		$db = $this->getDB();
		$table = $db->{'Books'};
		$this->assertNotNull($table);
		$this->assertInstanceOf('jamend\Selective\RecordSet', $table);
	}
	
	public function testOpenRecordSet() {
		$db = $this->getDB();
		$table = $db->{'Books'};
		$table2 = $table->openRecordSet();
		$this->assertNotNull($table2);
		$this->assertInstanceOf('jamend\Selective\RecordSet', $table2);
		$this->assertTrue($table !== $table2);
	}
	
	public function testGetByID() {
		$db = $this->getDB();
		$table = $db->{'Books'};
		$id = 1;
		$record = $table->{$id};
		$this->assertNotSame(false, $record);
		$this->assertInstanceOf('jamend\Selective\Record', $record);
		$this->assertEquals($record->getID(), $id);
	}
}