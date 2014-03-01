<?php
namespace jamend\Selective;

/**
 * Represents a record in a table
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Record {
	// Keep any special properties in __meta so that any instances look more like plain old objects
	private $__meta = array();
	
	/**
	 * Get a record in the given table.
	 * This class is usually instantiated by \PDOStatement::fetchObject, which
	 * sets column values as properties.
	 * @param Table $table
	 * @param string $exists Is this a real record, or a new one that we will probably insert later?
	 */
	public function __construct(Table $table, $exists = true) {
		$this->__meta['table'] = $table;
		$this->__meta['exists'] = $exists;
		$this->__meta['existed'] = $exists;
	}
	
	/**
	 * Get this record's table
	 * @return Table
	 */
	public function getTable() {
		return $this->__meta['table'];
	}
	
	/**
	 * Returns true if this record exists in the table
	 * @return boolean
	 */
	public function exists() {
		return $this->__meta['exists'];
	}
	
	/**
	 * Returns true if this record ever existed, i.e. if it was deleted
	 * @return boolean
	 */
	public function existed() {
		return $this->__meta['existed'];
	}
	
	/**
	 * Get the ID of this record; for a multiple-primary key table, the PK
	 * values are joined by commas
	 * @return string
	 */
	public function getID() {
		$key = '';
		foreach ($this->getTable()->getKeys() as $columnName) {
			$key .= ',' . $this->{$columnName};
		}
		return substr($key, 1);
	}
	
	/**
	 * Get the WHERE clause to identify this record by its primary key values
	 * @param &array $params array will to which prepared statement bind
	 * 	parameters will be added
	 */
	private function getIdentifyingWhereClause(&$params) {
		$keyCriteria = '';
		foreach ($this->getTable()->getKeys() as $columnName) {
			$column = $this->getTable()->getColumns()[$columnName];
			$keyCriteria .= " AND {$column->getBaseIdentifier()} = ?";
			$params[] = $this->{$columnName};
		}
		return substr($keyCriteria, 5); // remove first ' AND '
	}
	
	/**
	 * Saves this record in the table; This will result in an INSERT or UPDATE
	 * query based of if this record already exists
	 * @return boolean True if a change was made to the database  
	 */
	public function save() {
		if ($this->exists()) {
			$affectedRows = $this->update();
		} else {
			$affectedRows = $this->insert();
		}
		
		$this->__meta['exists'] = $affectedRows !== false;
		return $this->__meta['exists'];
	}

	/**
	 * Update the existing record in the database
	 * @return int number of affected rows, or false
	 */
	private function update() {
		$params = array();
		
		// Build an update query for an existing record
		$update = '';
		foreach ($this->getTable()->getColumns() as $columnName => $column) {
			$update .= ", {$column->getBaseIdentifier()} = ?";
			$params[] = $this->{$columnName};
		}
		$update = substr($update, 2); // remove first ', '
		
		$keyCriteria = $this->getIdentifyingWhereClause($params);
		
		return $this->getTable()->getDB()->executeUpdate(
			"UPDATE {$this->getTable()->getFullIdentifier()} SET {$update} WHERE {$keyCriteria}",
			$params
		);
	}
	
	/**
	 * Insert the record into the database
	 * @return int number of affected rows, or false
	 */
	private function insert() {
		$params = array();
		
		// Build an insert query for a new record
		$fields = '';
		$values = '';
		foreach ($this->getTable()->getColumns() as $columnName => $column) {
			$fields .= ", {$column->getBaseIdentifier()}";
			$values .= ', ?';
			$params[] = $this->{$columnName};
		}
		$fields = substr($fields, 2); // remove first ', '
		$values = substr($values, 2); // remove first ', '
		
		return $this->getTable()->getDB()->executeUpdate(
			"INSERT INTO {$this->getTable()->getFullIdentifier()} ({$fields}) VALUES ({$values})",
			$params
		);
	}
	
	/**
	 * Delete this record from the database
	 * @return boolean True if the record is deleted
	 */
	public function delete() {
		$params = array();
		$keyCriteria = $this->getIdentifyingWhereClause($params);
		
		$affectedRows = $this->getTable()->getDB()->executeUpdate("DELETE FROM {$this->getTable()->getFullIdentifier()} WHERE {$keyCriteria}", $params);
		$this->__meta['exists'] = $affectedRows === false && $this->__meta['exists'];
		return !$this->__meta['exists'];
	}
}