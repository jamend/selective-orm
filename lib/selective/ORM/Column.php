<?php
namespace selective\ORM;

/**
 * Represents a table column
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Column
{
    private $table;
    private $driver;
    private $name;
    private $ordinal;
    private $default = null;
    private $isPrimaryKey = false;
    private $isAutoIncrement = false;
    private $allowNull = true;
    private $type;
    private $length;
    private $options = [];

    /**
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->driver = $table->getDriver();
    }

    /**
     * Get the table of this column
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the driver
     * @return \selective\ORM\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the column name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the column name
     * @param string $name
     * @return Column fluent interface
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the ordinal of the column in the table
     * @return int
     */
    public function getOrdinal()
    {
        return $this->ordinal;
    }

    /**
     * Set the ordinal of the column in the table
     * @param int $ordinal
     * @return Column fluent interface
     */
    public function setOrdinal($ordinal)
    {
        $this->ordinal = $ordinal;
        return $this;
    }

    /**
     * Get the default value
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the default value
     * @param mixed $default
     * @return Column fluent interface
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Check if the column is a primary key
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->isPrimaryKey;
    }

    /**
     * Set if the column is a primary key
     * @param bool $isPrimaryKey
     * @return Column fluent interface
     */
    public function setPrimaryKey($isPrimaryKey)
    {
        $this->isPrimaryKey = $isPrimaryKey;
        return $this;
    }

    /**
     * Check if the column is auto-incrementing
     * @return bool
     */
    public function isAutoIncrement()
    {
        return $this->isAutoIncrement;
    }

    /**
     * Set if the column is auto-incrementing
     * @param bool $isAutoIncrement
     * @return Column fluent interface
     */
    public function setAutoIncrement($isAutoIncrement)
    {
        $this->isAutoIncrement = $isAutoIncrement;
        return $this;
    }

    /**
     * Check if the column allows nulls
     * @return bool
     */
    public function isAllowNull()
    {
        return $this->allowNull;
    }

    /**
     * Set if the column allows nulls
     * @param bool $allowNull
     * @return Column fluent interface
     */
    public function setAllowNull($allowNull)
    {
        $this->allowNull = $allowNull;
        return $this;
    }

    /**
     * Get the native database type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the native database type
     * @param string $type
     * @return Column fluent interface
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the maximum length of the value
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set the maximum length of the value
     * @param int $length
     * @return Column fluent interface
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    /**
     * Get enum/set options
     * @return string[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set enum/set options
     * @param string[] $options
     * @return Column fluent interface
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get the SQL expression to get the normalized value for this column
     * @return string
     */
    public function getSQLExpression()
    {
        return $this->getDriver()->getColumnSQLExpression($this);
    }

    /**
     * GGet the DB implementation-specific representation of a value for this column
     * @param mixed $value
     * @return mixed
     */
    public function getColumnDenormalizedValue($value)
    {
        return $this->getDriver()->getColumnDenormalizedValue($this, $value);
    }

    /**
     * Get the full quoted identifier including database/table name
     * @return string
     */
    public function getFullIdentifier()
    {
        return $this->getDriver()->getColumnFullIdentifier($this);
    }

    /**
     * Get the quoted identifier for the column name
     * @return string
     */
    public function getBaseIdentifier()
    {
        return $this->getDriver()->getColumnBaseIdentifier($this);
    }
}