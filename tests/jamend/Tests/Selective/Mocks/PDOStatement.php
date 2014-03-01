<?php
namespace jamend\Tests\Selective\Mocks;

class PDOStatement extends \PDOStatement {
	private $sql;
	private $fakeData = array(
		'SHOW TABLES FROM `test`' => array(
			0 =>
			array (
				'Tables_in_sample' => 'Author',
			),
			1 =>
			array (
				'Tables_in_sample' => 'Book',
			),
		),
		'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?' => array(
			0 => array (
				'TABLE_CATALOG' => 'def',
				'TABLE_SCHEMA' => 'sample',
				'TABLE_NAME' => 'book',
				'COLUMN_NAME' => 'idBook',
				'ORDINAL_POSITION' => '1',
				'COLUMN_DEFAULT' => NULL,
				'IS_NULLABLE' => 'NO',
				'DATA_TYPE' => 'int',
				'CHARACTER_MAXIMUM_LENGTH' => NULL,
				'CHARACTER_OCTET_LENGTH' => NULL,
				'NUMERIC_PRECISION' => '10',
				'NUMERIC_SCALE' => '0',
				'DATETIME_PRECISION' => NULL,
				'CHARACTER_SET_NAME' => NULL,
				'COLLATION_NAME' => NULL,
				'COLUMN_TYPE' => 'int(11)',
				'COLUMN_KEY' => 'PRI',
				'EXTRA' => 'auto_increment',
				'PRIVILEGES' => 'select,insert,update,references',
				'COLUMN_COMMENT' => '' 
			),
			1 => array (
				'TABLE_CATALOG' => 'def',
				'TABLE_SCHEMA' => 'sample',
				'TABLE_NAME' => 'book',
				'COLUMN_NAME' => 'title',
				'ORDINAL_POSITION' => '2',
				'COLUMN_DEFAULT' => NULL,
				'IS_NULLABLE' => 'NO',
				'DATA_TYPE' => 'varchar',
				'CHARACTER_MAXIMUM_LENGTH' => '128',
				'CHARACTER_OCTET_LENGTH' => '384',
				'NUMERIC_PRECISION' => NULL,
				'NUMERIC_SCALE' => NULL,
				'DATETIME_PRECISION' => NULL,
				'CHARACTER_SET_NAME' => 'utf8',
				'COLLATION_NAME' => 'utf8_general_ci',
				'COLUMN_TYPE' => 'varchar(128)',
				'COLUMN_KEY' => '',
				'EXTRA' => '',
				'PRIVILEGES' => 'select,insert,update,references',
				'COLUMN_COMMENT' => '' 
			),
			2 => array (
				'TABLE_CATALOG' => 'def',
				'TABLE_SCHEMA' => 'sample',
				'TABLE_NAME' => 'book',
				'COLUMN_NAME' => 'idAuthor',
				'ORDINAL_POSITION' => '3',
				'COLUMN_DEFAULT' => NULL,
				'IS_NULLABLE' => 'NO',
				'DATA_TYPE' => 'int',
				'CHARACTER_MAXIMUM_LENGTH' => NULL,
				'CHARACTER_OCTET_LENGTH' => NULL,
				'NUMERIC_PRECISION' => '10',
				'NUMERIC_SCALE' => '0',
				'DATETIME_PRECISION' => NULL,
				'CHARACTER_SET_NAME' => NULL,
				'COLLATION_NAME' => NULL,
				'COLUMN_TYPE' => 'int(11)',
				'COLUMN_KEY' => 'MUL',
				'EXTRA' => '',
				'PRIVILEGES' => 'select,insert,update,references',
				'COLUMN_COMMENT' => '' 
			),
			3 => array (
				'TABLE_CATALOG' => 'def',
				'TABLE_SCHEMA' => 'sample',
				'TABLE_NAME' => 'book',
				'COLUMN_NAME' => 'isbn',
				'ORDINAL_POSITION' => '4',
				'COLUMN_DEFAULT' => NULL,
				'IS_NULLABLE' => 'NO',
				'DATA_TYPE' => 'varchar',
				'CHARACTER_MAXIMUM_LENGTH' => '32',
				'CHARACTER_OCTET_LENGTH' => '96',
				'NUMERIC_PRECISION' => NULL,
				'NUMERIC_SCALE' => NULL,
				'DATETIME_PRECISION' => NULL,
				'CHARACTER_SET_NAME' => 'utf8',
				'COLLATION_NAME' => 'utf8_general_ci',
				'COLUMN_TYPE' => 'varchar(32)',
				'COLUMN_KEY' => '',
				'EXTRA' => '',
				'PRIVILEGES' => 'select,insert,update,references',
				'COLUMN_COMMENT' => '' 
			),
			4 => array (
				'TABLE_CATALOG' => 'def',
				'TABLE_SCHEMA' => 'sample',
				'TABLE_NAME' => 'book',
				'COLUMN_NAME' => 'description',
				'ORDINAL_POSITION' => '5',
				'COLUMN_DEFAULT' => NULL,
				'IS_NULLABLE' => 'YES',
				'DATA_TYPE' => 'text',
				'CHARACTER_MAXIMUM_LENGTH' => '65535',
				'CHARACTER_OCTET_LENGTH' => '65535',
				'NUMERIC_PRECISION' => NULL,
				'NUMERIC_SCALE' => NULL,
				'DATETIME_PRECISION' => NULL,
				'CHARACTER_SET_NAME' => 'utf8',
				'COLLATION_NAME' => 'utf8_general_ci',
				'COLUMN_TYPE' => 'text',
				'COLUMN_KEY' => '',
				'EXTRA' => '',
				'PRIVILEGES' => 'select,insert,update,references',
				'COLUMN_COMMENT' => '' 
			),
		),
		'SELECT `idBook`, `title`, `idAuthor`, `isbn`, `description` FROM `test`.`Book` WHERE `idBook` = ?' => array(
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