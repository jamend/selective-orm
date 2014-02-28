<?php
namespace jamend\Tests\Selective;

class MockPDO extends \PDO {
	public function __construct() {}
	
	public function prepare($statement, $options = null) {
		return new MockStatement($statement);
	}
}