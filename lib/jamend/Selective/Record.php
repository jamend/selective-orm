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
	 * This class is usually instantiated by PDOStatement::fetchObject, which
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
	 * Saves this record in the table; This will result in an INSERT or UPDATE
	 * query based of if this record already exists
	 * @return boolean True if a change was made to the database  
	 */
	public function save() {
		$params = array();
		
		if ($this->exists()) {
			// Build an update query for an existing record
			$update = '';
			foreach ($this->getTable()->getColumns() as $columnName => $column) {
				$update .= ', ' . $this->getTable()->getFullName() . '.`' . $columnName . '` = ?';
				$params[] = $this->{$columnName};
			}
			
			$keyCriteria = '';
			foreach ($this->getTable()->getKeys() as $columnName) {
				$keyCriteria .= ' AND ' . $this->getTable()->getFullName() . '.`' . $columnName . '` = ?';
				$params[] = $this->{$columnName};
			}
			
			$query = 'UPDATE ' . $this->getTable()->getFullName() . ' SET ' . substr($update, 2) . ' WHERE ' . substr($keyCriteria, 5);
		} else {
			// Build an insert query for a new record
			$fields = '';
			$values = '';
			foreach ($this->getTable()->getColumns() as $columnName => $column) {
				$fields .= ', `' . $columnName . '`';
				$values .= ', ?';
				$params[] = $this->{$columnName};
			}
			$query = 'INSERT INTO ' . $this->getTable()->getFullName() . ' (' . substr($fields, 2) . ') VALUES (' . substr($values, 2) . ')';
		}
		
		$affectedRows = $this->__meta['exists'] = $this->getTable()->getDB()->executeUpdate($query, $params);
		$this->__meta['exists'] = $affectedRows !== false;
		return $this->__meta['exists'];
	}
	
	/**
	 * Delete this record from the database
	 * @return boolean True if the record was deleted
	 */
	public function delete() {
		$params = array();
		$keyCriteria = '';
		
		// Build a query to delete this record based on its primary key value(s)
		foreach ($this->getTable()->getKeys() as $columnName) {
			$params[] = $this->{$columnName};
			$keyCriteria .= ' AND ' . $this->getTable()->getFullName() . '.`' . $columnName . '` = ?';
		}
		
		$affectedRows = $this->getTable()->getDB()->executeUpdate('DELETE FROM ' . $this->getTable()->getFullName() . ' WHERE ' . substr($keyCriteria, 5), $params);
		$this->__meta['exists'] = $affectedRows === false && $this->__meta['exists'];
		return !$this->__meta['exists'];
	}
}