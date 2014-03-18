<?php
namespace jamend\Selective;

/**
 * lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
interface Driver
{
    /**
     * Load connection parameters
     * @param array $parameters
     */
    public function loadParameters($parameters);

    /**
     * Connect to the database
     * @param Database $database
     */
    public function connect(Database $database);

    /**
     * Get a list of names of the table in a database
     * @param Database $database
     * @return string[]
     */
    public function getTables(Database $database);

    /**
     * Get a jamend\Selective\Table by name
     * @param string $name
     * @param Database $database
     * @return Table
     */
    public function getTable(Database $database, $name);

    /**
     * Quote a value for use in SQL statements
     * @param mixed $value
     */
    public function quote($value);

    /**
     * Execute an update query and return the number of affected rows
     * @param string $sql
     * @param array $params
     * @throws \Exception
     * @return number of affected rows
     */
    public function executeUpdate($sql, $params = null);

    /**
     * Get an array of all rows of a statement as associative arrays
     * @param string $sql
     * @param array $params
     * @param string $indexField
     * @param string $groupField
     * @return array[]
     */
    public function fetchAll($sql, $params = array(), $indexField = null, $groupField = null);

    /**
     * Get the full quoted identifier including database name
     * @param Table $table
     * @return string
     */
    public function getTableFullIdentifier(Table $table);

    /**
     * Get the quoted identifier for the table name
     * @param Table $table
     * @return string
     */
    public function getTableBaseIdentifier(Table $table);

    /**
     * Get the full quoted identifier including database/table name
     * @param Column $column
     * @return string
     */
    public function getColumnFullIdentifier(Column $column);

    /**
     * Get the quoted identifier for the column name
     * @param Column $column
     * @return string
     */
    public function getColumnBaseIdentifier(Column $column);

    /**
     * Get the SQL expression to get the normalized value for a column
     * @param Column $column
     * @return string
     */
    public function getColumnSQLExpression(Column $column);

    /**
     * Get the implementation-specific representation of a value for a column
     * @param Column $column
     * @param mixed $value
     * @return mixed
     */
    public function getColumnDenormalizedValue(Column $column, $value);

    /**
     * Get a table's records for the given query
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @return \jamend\Selective\Record[]
     */
    public function getRecords(\jamend\Selective\Table $table, \jamend\Selective\Query $query);

    /**
     * Update an existing record in the database
     * @param \jamend\Selective\Record $record
     * @return int number of affected rows, or false
     */
    public function updateRecord($record);

    /**
     * Insert a record into the database
     * @param \jamend\Selective\Record $record
     * @return int number of affected rows, or false
     */
    public function insertRecord($record);

    /**
     * Delete a record from the database
     * @param \jamend\Selective\Record $record
     * @return boolean True if the record is deleted
     */
    public function deleteRecord($record);
}