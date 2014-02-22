<?php
namespace jamend\Selective;

/**
 * Represents the records in a table in the database, that can be used like an array
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class RecordSet implements \IteratorAggregate, \ArrayAccess, \Countable {
	private $dirty;
	private $query;
	private $records;
	
	/**
	 * Make a record set for the given table
	 * @param Table $table
	 */
	public function __construct(Table $table) {
		$this->table = $table;
		$this->dirty = true;
		// Set a default where clause
		$this->query = array('where' => array(1));
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
		$result = $this->getTable()->getDB()->query($this->buildQuery($id));
		$record = $this->getTable()->getDB()->fetchObject($result, 'jamend\Selective\Record', array($this->getTable()));
		return $record;
	}
	
	/**
	 * Return a new record set filtered by the given where clause
	 * @param string $criteria where clause
	 * @return \jamend\Selective\RecordSet
	 */
	public function where($criteria) {
		$recordSet = $this->openRecordSet();
		$recordSet->query['where'][] = $criteria;
		return $recordSet;
	}
	
	/**
	 * Build the SQL query for this record set
	 * @param string $id Build the query to get the record with the ID from the table
	 * @return string SQL query
	 */
	private function buildQuery($id = NULL) {
		$where = '(' . implode(') AND (', $this->query['where']) . ')';
		if ($id !== NULL) {
			// Build a where clause to find a record by its ID
			$idParts = explode(',', $id);
			for ($i = 0; $i < count($idParts); $i++) {
				$where .= ' AND ' . $this->getTable()->getFullName() . '.`' . $this->getTable()->getKeys()[$i] . '` = ' . DB::quote($idParts[$i]);
			}
		}
		$columns = '';
		// Add each column to the query
		foreach ($this->getTable()->getColumns() as $columnName => $column) {
			$columns .= ', ' . $this->getTable()->getFullName() . '.`' . $columnName . '`';
			// Force columns of type set to return the numeric value instead of the string that it maps to
			if ($column->type == 'set') $columns .= ' + 0 AS `' . $columnName . '`';
		}
		return 'SELECT ' . substr($columns, 2) . ' FROM ' . $this->getTable()->getFullName() . ' WHERE ' . $where;
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
		$result = $this->getTable()->getDB()->query($this->buildQuery());
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
	
	public function count() {
		if ($this->isDirty()) $this->load();
		return count($this->records);
	}
	
	public function offsetExists($offset) {
		if ($this->isDirty()) $this->load();
		return isset($this->records[$offset]);
	}
	
	public function offsetGet($offset) {
		if ($this->isDirty()) $this->load();
		return isset($this->records[$offset]) ? $this->records[$offset] : null;
	}
	
	public function offsetSet($offset, $value) {
		if ($this->isDirty()) $this->load();
		return $this->records[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset(self::$_cache[$this->_cacheKey][$offset]);
		unset($this->records[$offset]);
	}
	
	public function getIterator() {
		if ($this->isDirty()) $this->load();
		return new \ArrayIterator($this->records);
	}
}