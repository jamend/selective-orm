<?php
namespace jamend\Selective;

/**
 * Represents a table column
 * FIXME public properties should be replaced with getters/setters
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Column {
	private $table;
	private $name;
	private $default = null;
	private $isPrimaryKey;
	private $allowNull;
	private $type;
	private $length;
	private $options = array();
	
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
	 * Set the column name
	 * @param string $name
	 * @return Column fluent interface
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get the default value
	 * @return mixed
	 */
	public function getDefault() {
		return $this->default;
	}
	
	/**
	 * Set the default value
	 * @param mixed $default
	 * @return Column fluent interface
	 */
	public function setDefault($default) {
		$this->default = $default;
		return $this;
	}
	
	/**
	 * Check if the column is a primary key
	 * @return bool
	 */
	public function isPrimaryKey() {
		return $this->isPrimaryKey;
	}
	
	/**
	 * Set if the column is a primary key
	 * @param bool $isPrimaryKey
	 * @return Column fluent interface
	 */
	public function setPrimaryKey($isPrimaryKey) {
		$this->isPrimaryKey = $isPrimaryKey;
		return $this;
	}
	
	/**
	 * Check if the column allows nulls
	 * @return bool
	 */
	public function isAllowNull() {
		return $this->allowNull;
	}
	
	/**
	 * Set if the column allows nulls
	 * @param bool $allowNull
	 * @return Column fluent interface
	 */
	public function setAllowNull($allowNull) {
		$this->allowNull = $allowNull;
		return $this;
	}
	
	/**
	 * Get the native database type
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * Get the native database type
	 * @param string $type
	 * @return Column fluent interface
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}
	
	/**
	 * Get the maximum length of the value
	 * @return int
	 */
	public function getLength() {
		return $this->length;
	}
	
	/**
	 * Set the maximum length of the value
	 * @param int $length
	 * @return Column fluent interface
	 */
	public function setLength($length) {
		$this->length = $length;
		return $this;
	}
	
	/**
	 * Get enum/set options
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}
	
	/**
	 * Set enum/set options
	 * @param array $options
	 * @return Column fluent interface
	 */
	public function setOptions($options) {
		$this->options = $options;
		return $this;
	}
	
	/**
	 * Get the SQL expression to get the normalized value for this column
	 * @return string
	 */
	public function getSQLExpression() {
		return $this->getTable()->getDB()->getColumnSQLExpression($this);
	}
	
	/**
	 * GGet the DB implementation-specific representation of a value for this column
	 * @param mixed $value
	 * @return mixed
	 */
	public function getColumnDenormalizedValue($value) {
		return $this->getTable()->getDB()->getColumnDenormalizedValue($this, $value);
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