<?php
namespace jamend\Selective;

/**
 * Represents a record in a table
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Record {
	// Keep internal state in _meta so that any instances look more like plain old objects
	private $_meta = array();
	
	/**
	 * Get a record in the given table.
	 * This class is usually instantiated by \PDOStatement::fetchObject, which
	 * sets column values as properties.
	 * @param Table $table
	 * @param string $exists Is this a real record, or a new one that we will probably insert later?
	 */
	public function __construct(Table $table, $exists = true) {
		$this->_meta['table'] = $table;
		$this->_meta['exists'] = $exists;
		$this->_meta['existed'] = $exists;
		foreach ($table->getForeignKeys() as $localColumn => $foreignKey) {
			$constraint = $this->getTable()->constraints[$foreignKey];
			$this->_meta['foreignRecords'][$localColumn] = $this->{$localColumn};
			// unset the property, so that the magic __getter will be invoked
			unset($this->{$localColumn});
		}
	}
	
	/**
	 * Get this record's table
	 * @return Table
	 */
	public function getTable() {
		return $this->_meta['table'];
	}
	
	/**
	 * Returns true if this record exists in the table
	 * @return boolean
	 */
	public function exists() {
		return $this->_meta['exists'];
	}
	
	/**
	 * Returns true if this record ever existed, i.e. if it was deleted
	 * @return boolean
	 */
	public function existed() {
		return $this->_meta['existed'];
	}
	
	/**
	 * Get the ID of this record; for a multiple-primary key table, the PK
	 * values are joined by commas
	 * @return string
	 */
	public function getID() {
		$key = '';
		foreach ($this->getTable()->getPrimaryKeys() as $columnName) {
			$key .= ',' . $this->{$columnName};
		}
		return substr($key, 1);
	}
	
	/**
	 * Get a table related to this record by table name
	 * @param string $tableName
	 * @return \jamend\Selective\Table|boolean
	 */
	public function getRelatedTable($tableName) {
		if ($this->getTable()->getDB()->hasTable($tableName)) {
			$relatedTable = $this->getTable()->getDB()->getTable($tableName);
			if (isset($relatedTable->relatedTables[$this->getTable()->getName()])) {
				$constraintName = $relatedTable->relatedTables[$this->getTable()->getName()];
				$constraint = $relatedTable->constraints[$constraintName];
				
				for ($i = 0; $i < count($constraint['localColumns']); $i++) {
					$localColumn = $relatedTable->getColumns()[$constraint['localColumns'][$i]];
					$foreignColumnName = $constraint['relatedColumns'][$i];
					$relatedTable = $relatedTable->where($localColumn->getFullIdentifier() . ' = ?', $this->{$foreignColumnName});
				}
				
				return $relatedTable;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Get the related record by value of the given column name
	 * @param string $columnName
	 * @return Ambigous \jamend\Selective\Record|boolean
	 */
	public function getForeignRecord($columnName) {
		if (isset($this->_meta['foreignRecords'][$columnName])) {
			$constraintName = $this->getTable()->getForeignKeys()[$columnName];
			$constraint = $this->getTable()->constraints[$constraintName];
			$relatedTable = $this->getTable()->getDB()->getTable($constraint['relatedTable']);
			
			for ($i = 0; $i < count($constraint['localColumns']); $i++) {
				$localColumn = $relatedTable->getColumns()[$constraint['localColumns'][$i]];
				$foreignColumnName = $constraint['relatedColumns'][$i];
				$relatedTable = $relatedTable->where($localColumn->getFullIdentifier() . ' = ?', $this->_meta['foreignRecords'][$localColumn->getName()]);
			}
			
			return $relatedTable->first();
		} else {
			return false;
		}
	}
	
	/**
	 * Get a table related to this record by table name
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		if (($table = $this->getRelatedTable($name)) !== false) {
			return $table;
		} else if (($foreignRecord = $this->getForeignRecord($name)) !== false) {
			return $foreignRecord;
		} else {
			trigger_error('Undefined property: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
			return null;
		}
	}
	
	/**
	 * Checks if a table related to this record exists by table name
	 * @param string $tableName
	 * @return \jamend\Selective\Table|boolean
	 */
	public function hasRelatedTable($tableName) {
		if ($this->getTable()->getDB()->hasTable($tableName)) {
			$relatedTable = $this->getTable()->getDB()->getTable($tableName);
			return isset($relatedTable->relatedTables[$this->getTable()->getName()]);
		} else {
			return false;
		}
	}

	/**
	 * Checks if there is a related record by value of the given column name
	 * @param string $columnName
	 * @return Ambigous \jamend\Selective\Record|boolean
	 */
	public function hasForeignRecord($columnName) {
		return isset($this->_meta['foreignRecords'][$columnName]);
	}

	/**
	 * Checks if a table related to this record exists by table name
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		return $this->hasRelatedTable($name) || $this->hasForeignRecord($name);
	}
	
	/**
	 * Get the WHERE clause to identify this record by its primary key values
	 * @param &array $params array will to which prepared statement bind
	 * 	parameters will be added
	 */
	private function getIdentifyingWhereClause(&$params) {
		$keyCriteria = '';
		foreach ($this->getTable()->getPrimaryKeys() as $columnName) {
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
		
		$this->_meta['exists'] = $affectedRows !== false;
		return $this->_meta['exists'];
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
			$params[] = $column->getColumnDenormalizedValue($this->{$columnName});
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
			$params[] = $column->getColumnDenormalizedValue($this->{$columnName});
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
		$this->_meta['exists'] = $affectedRows === false && $this->_meta['exists'];
		return !$this->_meta['exists'];
	}
}