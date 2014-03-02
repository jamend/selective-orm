<?php
namespace jamend\Selective;

/**
 * Represents a table column
 * FIXME public properties should be replaced with getters/setters
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Column {
	public $table;
	public $name;
	public $default = null;
	public $isPrimaryKey;
	public $allowNull;
	public $type;
	public $length;
	public $options = array();
	
	/**
	 * @param \jamend\ORM\Table $table
	 */
	public function __construct($table) {
		$this->table = $table;
	}
	
	/**
	 * Get the table of this column
	 * @return Table
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * Get the column name
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Get the full quoted identifier including database/table name
	 * @return string
	 */
	public function getFullIdentifier() {
		return $this->getTable()->getDB()->getColumnFullIdentifier($this);
	}
	
	/**
	 * Get the quoted identifier for the column name
	 * @return string
	 */
	public function getBaseIdentifier() {
		return $this->getTable()->getDB()->getColumnBaseIdentifier($this);
	}
}