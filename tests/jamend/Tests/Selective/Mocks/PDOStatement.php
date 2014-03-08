<?php
namespace jamend\Tests\Selective\Mocks;

class PDOStatement extends \PDOStatement {
	private $sql;
	private $fakeData = array(
		'SHOW TABLES FROM `test`' => array(
			0 =>
			array (
				'Tables_in_sample' => 'Authors',
			),
			1 =>
			array (
				'Tables_in_sample' => 'Books',
			),
		),
		'SHOW CREATE TABLE `Books`' => array(
			0 => array (
				'Table' => 'Books',
				'Create Table' => 'CREATE TABLE `Books` (
  `idBook` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `idAuthor` int(11) NOT NULL,
  `isbn` varchar(32) NOT NULL,
  `description` text,
  PRIMARY KEY (`idBook`),
  KEY `idAuthor` (`idAuthor`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`idAuthor`) REFERENCES `authors` (`idAuthor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8' 
			),
		),
		'SELECT `idBook`, `title`, `idAuthor`, `isbn`, `description` FROM `test`.`Books` WHERE `idBook` = ?' => array(
			0 =>
			array (
				'idBook' => '1',
				'title' => 'My First Book',
				'idAuthor' => '1',
				'isbn' => '12345-6789',
				'description' => 'It wasn\'t very good',
			),
		)
	);
	
	private $affectedRows = 0;
	
	public function __construct($sql) {
		$this->sql = $sql;
	}
	
	public function execute($bound_input_params = null) {
		switch (strtolower(substr($this->sql, 5))) {
			case 'insert':
			case 'update':
			case 'delete':
				$this->affectedRows = 1;
				break;
			case 'select':
				$this->affectedRows = 0;
				break;
		}
		return true;
	}
	
	public function rowCount() {
		return $this->affectedRows;
	}
	
	public function fetch($how = null, $orientation = null, $offset = null) {
		$row = current($this->fakeData[$this->sql]);
		next($this->fakeData[$this->sql]);
		return $row;
	}
	
	public function fetchObject($class_name = null, $ctor_args = null) {
		if ($data = $this->fetch()) {
			if (!$class_name) $class_name = '\stdClass';
			$rflClass = new \ReflectionClass($class_name);
			$object = $rflClass->newInstanceArgs($ctor_args);
			foreach ($data as $key => $value) {
				$object->$key = $value;
			}
			return $object;
		} else {
			return false;
		}
	}
}