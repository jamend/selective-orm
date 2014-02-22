<?php
namespace jamend\Selective;

/**
 * Represents the records in a table in the database, that can be used like an array
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class RecordSet implements \IteratorAggregate, \ArrayAccess, \Countable {
	private $dirty = true;
	private $query = array(
		'where' => array(),
		'having' => array(),
	);
	private $records;
	
	/**
	 * Make a record set for the given table
	 * @param Table $table
	 */
	public function __construct(Table $table) {
		$this->table = $table;
	}
	
	/**
	 * Get the table of this 
	 * @return \jamend\Selective\Table
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * Get a clone of this record set, ready for more filters/criteria
	 * @return \jamend\Selective\RecordSet
	 */
	public function openRecordSet() {
		return clone $this;
	}
	
	/**
	 * Get the record with the given ID from this record set
	 * @param string $id
	 * @return Record
	 */
	public function __get($id) {
		$result = $this->query($id);
		$record = $this->getTable()->getDB()->fetchObject($result, 'jamend\Selective\Record', array($this->getTable()));
		return $record;
	}
	
	/**
	 * Return a new record set filtered by the given where clause
	 * @param string $criteria where clause
	 * @param mixed... $params
	 * @return \jamend\Selective\RecordSet
	 */
	public function where($criteria) {
		$params = func_get_args();
		$criteria = array_shift($params);
		$recordSet = $this->openRecordSet();
		$recordSet->query['where'][] = array($criteria, $params);
		return $recordSet;
	}
	
	/**
	 * Return a new record set filtered by the given having clause
	 * @param string $criteria where clause
	 * @param mixed... $params
	 * @return \jamend\Selective\RecordSet
	 */
	public function having($criteria) {
		$params = func_get_args();
		$criteria = array_shift($params);
		$recordSet = $this->openRecordSet();
		$recordSet->query['having'][] = array($criteria, $params);
		return $recordSet;
	}
	
	/**
	 * Build the SQL query for this record set
	 * @param string $id Build the query to get the record with the ID from the table
	 * @return string SQL query
	 */
	private function query($id = null) {
		$params = array();
		
		// build where clause
		$where = '';
		foreach ($this->query['where'] as $whereClause) {
			$where .= ' AND (' . $whereClause[0] . ')';
			if (!empty($whereClause[1])) $params = array_merge($params, $whereClause[1]);
		}
		
		if ($id !== null) {
			// Build a where clause to find a record by its ID
			$idParts = explode(',', $id);
			for ($i = 0; $i < count($idParts); $i++) {
				$params[] = $idParts[$i];
				$where .= ' AND ' . $this->getTable()->getFullName() . '.`' . $this->getTable()->getKeys()[$i] . '` = ?';
			}
		}
		
		if ($where) $where = ' WHERE ' . substr($where, 5); // replace first AND with WHERE
		
		// build having clause
		$having = '';
		foreach ($this->query['having'] as $havingClause) {
			$having .= ' AND (' . $havingClause[0] . ')';
			if (!empty($havingClause[1])) $params = array_merge($params, $havingClause[1]);
		}
		if ($having) $having = ' HAVING ' . substr($having, 5); // replace first AND with HAVING
		
		$columns = '';
		// Add each column to the query
		foreach ($this->getTable()->getColumns() as $columnName => $column) {
			$columns .= ', ' . $this->getTable()->getFullName() . '.`' . $columnName . '`';
			// Force columns of type set to return the numeric value instead of the string that it maps to
			if ($column->type == 'set') $columns .= ' + 0 AS `' . $columnName . '`';
		}
		
		$sql = 'SELECT ' . substr($columns, 2) . ' FROM ' . $this->getTable()->getFullName() . $where . $having;
		return $this->getTable()->getDB()->query($sql, $params);
	}
	
	/**
	 * Tracks if the records have been loaded after a change in the query/criteria
	 * @return boolean
	 */
	public function isDirty() {
		return $this->dirty;
	}
	
	/**
	 * Load the records for this record set
	 */
	private function load() {
		$result = $this->query();
		$this->records = array();
		while ($record = $this->getTable()->getDB()->fetchObject($result, 'jamend\Selective\Record', array($this->getTable()))) {
			$this->records[$record->getID()] = $record;
		}
		$this->dirty == false;
	}
	
	/**
	 * Get the first record from this record set
	 * @return Record
	 */
	public function first() {
		if ($this->isDirty()) $this->load();
		reset($this->records);
		return current($this->records);
	}
	
	// Array iteration/traversal
	
	/**
	 * Get the count of records
	 * @return int
	 */
	public function count() {
		if ($this->isDirty()) $this->load();
		return count($this->records);
	}

	/**
	 * Check if a record exists by its ID
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		if ($this->isDirty()) $this->load();
		return isset($this->records[$offset]);
	}

	/**
	 * Get a record by its ID
	 * @param mixed $offset
	 * @return int
	 */
	public function offsetGet($offset) {
		if ($this->isDirty()) $this->load();
		return isset($this->records[$offset]) ? $this->records[$offset] : null;
	}

	/**
	 * Get a record by ID
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		if ($this->isDirty()) $this->load();
		return $this->records[$offset] = $value;
	}

	/**
	 * Remove a record by ID
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		unset($this->records[$offset]);
	}
	

	/**
	 * Get an iterator for the records
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		if ($this->isDirty()) $this->load();
		return new \ArrayIterator($this->records);
	}
}