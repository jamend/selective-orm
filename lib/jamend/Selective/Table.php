<?php
namespace jamend\Selective;

/**
 * Represents a table in the database
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Table extends RecordSet
{
    private $name;
    /**
     * @var Database
     */
    private $database;
    /**
     * @var Column[]
     */
    public $columns = array();
    public $primaryKeys = array();
    public $foreignKeys = array();
    public $relatedTables = array();
    public $constraints = array();
    /**
     * @var Driver
     */
    private $driver;

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
     * Get the driver
     * @return \jamend\Selective\Driver
     */
    public function getDriver()
    {
        return $this->driver;
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
     * Prepare a new Record to be saved in this table
     * @return Record
     */
    public function create()
    {
        $data = [];
        foreach ($this->getColumns() as $columnName => $column) {
            $data[$columnName] = $column->getDefault();
        }
        return new Record($this, false, $data);
    }
}