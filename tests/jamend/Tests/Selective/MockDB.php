<?php
namespace jamend\Tests\Selective;

class MockDB extends \jamend\Selective\DB\PDOMySQL {
	protected function connect() {
		$this->pdo = new MockPDO();
	}
}