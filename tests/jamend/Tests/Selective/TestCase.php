<?php
namespace jamend\Tests\Selective;

abstract class TestCase extends \PHPUnit_Framework_TestCase {
	protected function getDB() {
		return \jamend\Selective\DB::loadDB('test', '\jamend\Tests\Selective\Mocks\DB', array());
	}
}