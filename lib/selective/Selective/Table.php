<?php
namespace jamend\Selective;

/**
 * Represents a table in the database
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Table extends RecordSet\Buffered
{
    private $name;
    /**
     * @var Database
     */
    private $database;
    /**
     * @var Column[]
     */
    public $columns = [];
    public $primaryKeys = [];
    public $foreignKeys = [];
    public $relatedTables = [];
    public $constraints = [];

    /**
     * Get a table to match the one with the given name in the database
     * @param string $name
     * @param Database $database
     */
    public function __construct($name, Database $database)
    {
        $this->name = $name;
        $this->database = $database;
        $this->driver = $database->getDriver();

        parent::__construct($this);
    }

    /**
     * Get the table of this
     * @return \jamend\Selective\Table
     */
    public function getTable()
    {
        return $this;
    }

    /**
     * Get a clone of this record set, ready for more filters/criteria
     * @return RecordSet
     */
    public function openRecordSet()
    {
        $recordSet = new RecordSet\Buffered($this->getTable());
        $recordSet->query = clone $this->query;
        return $recordSet;
    }

    /**
     * Get the full quoted identifier including database name
     * @return string
     */
    public function getFullIdentifier()
    {
        return $this->getDriver()->getTableFullIdentifier($this);
    }

    /**
     * Get the quoted identifier for the table name
     * @return string
     */
    public function getBaseIdentifier()
    {
        return $this->getDriver()->getTableBaseIdentifier($this);
    }

    /**
     * Get the name of this table
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get this table's database
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Get an array of this table's columns
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get a column by name
     * @param $name
     * @return Column
     */
    public function getColumn($name)
    {
        return $this->columns[$name];
    }

    /**
     * Get an array of this table's primary keys
     * @return string[]
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Get an array of this table's foreign keys
     * @return array[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Get an array of this table's constraints
     * @return array[]
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * Get the record with the given ID from this table
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
     * @param mixed $name
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
     * Get the record with the given ID from this table
     * @param string $id
     * @return Record
     */
    public function getRecordByID($id)
    {
        if (!array_key_exists($id, $this->records)) {
            // build a where clause to find a record by its ID
            $idParts = explode(',', $id);
            $where = [];
            for ($i  = 0; $i < count($idParts); $i++) {
                $columnName = $this->getTable()->getPrimaryKeys()[$i];
                $column = $this->getTable()->getColumn($columnName);
                $where[] = ["{$column->getFullIdentifier()} = ?", [$idParts[$i]]];
            }

            $oldWhere = $this->query->getWhere();
            $this->query->setWhere($where);
            $hydrator = $this->getDriver()->getHydrator($this->getTable(), $this->query);
            $record = $hydrator->getRecord();
            $this->query->setWhere($oldWhere);

            if ($record) {
                $this->records[$id] = $record;
            } else {
                $this->records[$id] = null;
            }
        }

        return $this->records[$id];
    }

    /**
     * Prepare a new Record to be saved in this table
     * @return Record
     */
    public function create()
    {
        $record = new Record($this, false);
        foreach ($this->getColumns() as $columnName => $column) {
            $record->{$columnName} = $column->getDefault();
        }
        return $record;
    }
}