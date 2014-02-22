<?php
namespace jamend\Selective;

/**
 * Represents a table in the database
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Table extends RecordSet {
	private $name;
	private $db;
	private $columns = array();
	private $keys = array();
	
	/**
	 * Get a table to match the one with the given name in the database
	 * @param string $name
	 * @param DB $db
	 */
	public function __construct($name, $db) {
		$this->name = $name;
		$this->db = $db;
		
		// Get the list of columns
		$columnInfos = $this->getDB()->fetchAll('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', array($this->getDB()->getName(), $this->getName()));
		
		// Get the list of primary keys
		foreach ($columnInfos as $info) {
			$this->columns[$info['COLUMN_NAME']] = new Column($info, $this);
			if ($info['COLUMN_KEY'] == 'PRI') {
				$this->keys[] = $info['COLUMN_NAME'];
			}
		}
		
		parent::__construct($this);
	}
	
	/**
	 * Get the table of this 
	 * @return \jamend\ORM\Table
	 */
	public function getTable() {
		return $this;
	}
	
	/**
	 * Get the fully-qualified table name
	 * @return string
	 */
	public function getFullName() {
		return "`{$this->getDB()->getName()}`.`{$this->getName()}`";
	}
	
	/**
	 * Get the name of this table
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get this table's database
	 * @return DB
	 */
	public function getDB() {
		return $this->db;
	}

	/**
	 * Get an array of this table's columns
	 * @return array
	 */
	public function getColumns() {
		return $this->columns;
	}

	/**
	 * Get an array of this table's primary keys
	 * @return array
	 */
	public function getKeys() {
		return $this->keys;
	}

	/**
	 * Prepare a new Record to be saved in this table
	 * @return Record
	 */
	public function create() {
		$record = new Record($this, false);
		foreach ($this->getColumns() as $columnName => $column) {
			$record->{$columnName} = $column->default;
		}
		return $record;
	}
}