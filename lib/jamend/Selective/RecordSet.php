<?php
namespace jamend\Selective;

/**
 * Represents the records in a table in the database, that can be used like an
 * array
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class RecordSet implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var Table
     */
    private $table;
    /**
     * @var Query
     */
    private $query;
    /**
     * @var Driver
     */
    private $driver;
    /**
     * @var array
     */
    private $records = array();
    /**
     * @var bool
     */
    private $dirty = true;

    /**
     * Make a record set for the given table
     * @param Table $table
     */
    protected function __construct(Table $table)
    {
        $this->table = $table;
        $this->query = new Query();
        $this->driver = $table->getDriver();
    }

    /**
     * Get the table of this
     * @return \jamend\Selective\Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the query object
     * @return \jamend\Selective\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the driver
     * @return \jamend\Selective\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get a clone of this record set, ready for more filters/criteria
     * @return \jamend\Selective\RecordSet
     */
    public function openRecordSet()
    {
        $recordSet = new self($this->getTable());
        $recordSet->query = clone $this->query;
        return $recordSet;
    }

    /**
     * Return a new record set filtered by the given where clause
     * @param string $criteria where clause
     * @param mixed... $params
     * @return \jamend\Selective\RecordSet
     */
    public function where($criteria)
    {
        $params = func_get_args();
        $criteria = array_shift($params);
        $recordSet = $this->openRecordSet();
        $recordSet->query->addWhere($criteria, $params);
        return $recordSet;
    }

    /**
     * Return a new record set filtered by the given having clause
     * @param string $criteria where clause
     * @param mixed... $params
     * @return \jamend\Selective\RecordSet
     */
    public function having($criteria)
    {
        $params = func_get_args();
        $criteria = array_shift($params);
        $recordSet = $this->openRecordSet();
        $recordSet->query->addHaving($criteria, $params);
        return $recordSet;
    }

    /**
     * Return a new record set with the given limit clause
     * @param int $limit
     * @param int $offset
     * @return \jamend\Selective\RecordSet
     */
    public function limit($limit, $offset = 0)
    {
        $recordSet = $this->openRecordSet();
        $recordSet->query->setLimit($limit, $offset);
        return $recordSet;
    }

    /**
     * Return a new record set order by the given field and direction
     * @param string $field
     * @param string $direction ASC or DESC
     * @return \jamend\Selective\RecordSet
     */
    public function orderBy($field, $direction = 'ASC')
    {
        $recordSet = $this->openRecordSet();
        $recordSet->query->addOrderBy($field, $direction);
        return $recordSet;
    }

    /**
     * Pre-load related table with result set
     * @param string $tableName
     * @return \jamend\Selective\RecordSet
     */
    public function with($tableName)
    {
        $on = [];
        $joinType = 'left';
        $relatedTable = $this->getDatabase()->getTable($tableName);
        $columns = array_keys($relatedTable->getColumns());
        $cardinality = null;
        if (isset($relatedTable->relatedTables[$this->getTable()->getName()])) {
            $constraintName = $relatedTable->relatedTables[$this->getTable()->getName()];
            $constraint = $relatedTable->constraints[$constraintName];
            $cardinality = Query::CARDINALITY_MANY_TO_ONE;
        } else if ($this->getTable()->relatedTables[$relatedTable->getName()]) {
            $constraintName = $this->getTable()->relatedTables[$relatedTable->getName()];
            $constraint = $this->getTable()->constraints[$constraintName];
            $cardinality = Query::CARDINALITY_ONE_TO_MANY;
        } else {
            throw new Exception("Table {$this->getTable()->getName()} is not related to {$relatedTable->getName()}");
        }

        for ($i = 0; $i < count($constraint['localColumns']); $i++) {
            $relatedColumn = $relatedTable->getColumn($constraint['relatedColumns'][$i]);
            if (!$relatedColumn->isAllowNull()) {
                $joinType = 'inner';
            }
            $on[$constraint['relatedColumns'][$i]] = $constraint['localColumns'][$i];
        }

        $recordSet = $this->openRecordSet();
        $recordSet->query->addJoin($joinType, $tableName, $on, null, $columns, $cardinality);
        return $recordSet;
    }

    /**
     * Get the record with the given ID from this record set
     * @param string $name
     * @return Record
     */
    public function __get($name)
    {
        $record = $this->getRecordByID($name);
        if ($record) {
            return $record;
        } else {
            trigger_error('Undefined property: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Check if a record exists by its ID
     * @param mixed $offset
     * @return boolean
     */
    public function __isset($name)
    {
        $record = $this->getRecordByID($name);
        if ($record) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the record with the given ID from this record set
     * @param string $id
     * @return Record
     */
    public function getRecordByID($id)
    {
        if (!array_key_exists($id, $this->records)) {
            $query = clone $this->query;

            // build a where clause to find a record by its ID
            $idParts = explode(',', $id);
            for ($i = 0; $i < count($idParts); $i++) {
                $columnName = $this->getTable()->getPrimaryKeys()[$i];
                $column = $this->getTable()->getColumn($columnName);
                $query->addWhere("{$column->getBaseIdentifier()} = ?", array($idParts[$i]));
            }

            $records = $this->getDriver()->getRecords($this->getTable(), $query);
            if (count($records) == 0) {
                $this->records[$id] = null;
            } else {
                $this->records[$id] = current($records);
            }
        }

        return $this->records[$id];
    }

    /**
     * Load the records for this record set
     */
    private function load()
    {
        if ($this->dirty) {
            $this->records = $this->getDriver()->getRecords($this->getTable(), $this->query);
            $this->dirty = false;
        }
    }

    /**
     * Tracks if the records have been loaded after a change in the query/criteria
     * @return boolean
     */
    public function isDirty()
    {
        return $this->dirty;
    }

    /**
     * Get the first record from this record set
     * @return Record
     */
    public function first()
    {
        $this->load();
        reset($this->records);
        return current($this->records);
    }

    // Array iteration/traversal

    /**
     * Get the count of records
     * @return int
     */
    public function count()
    {
        $this->load();
        return count($this->records);
    }

    /**
     * Check if a record exists by its ID
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->load();
        return isset($this->records[$offset]);
    }

    /**
     * Get a record by its ID
     * @param mixed $offset
     * @return int
     */
    public function offsetGet($offset)
    {
        $this->load();
        return isset($this->records[$offset]) ? $this->records[$offset] : null;
    }

    /**
     * Get a record by ID
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->load();
        $this->records[$offset] = $value;
    }

    /**
     * Remove a record by ID
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->records[$offset]);
    }

    /**
     * Get an iterator for the records
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->load();
        return new \ArrayIterator($this->records);
    }
}