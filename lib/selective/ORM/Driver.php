<?php
namespace selective\ORM;

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
     * Get a selective\ORM\Table by name
     * @param string $name
     * @param Database $database
     * @return Table
     */
    public function buildTable(Database $database, $name);

    /**
     * Quote a value for use in SQL statements
     * @param mixed $value
     * @return string
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
     * @param Table $table
     * @param Query $query
     * @param bool $asArray return the records as arrays instead of objects
     * @return Hydrator
     */
    public function getHydrator(Table $table, Query $query, $asArray = false);

    /**
     * Starts a new transaction
     */
    public function startTransaction();

    /**
     * Commits the transaction
     */
    public function commit();

    /**
     * Rolls the transaction back
     */
    public function rollback();

    /**
     * Update an existing record in the database
     * @param Record $record
     * @return int number of affected rows, or false
     */
    public function updateRecord($record);

    /**
     * Insert a record into the database
     * @param Record $record
     * @return int number of affected rows, or false
     */
    public function insertRecord($record);

    /**
     * Delete a record from the database
     * @param Record $record
     * @return boolean True if the record is deleted
     */
    public function deleteRecord($record);

    /**
     * Enable/disable profiling
     * @param bool $profiling
     */
    public function setProfiling($profiling);

    /**
     * Check if profiling is enabled
     * @return bool
     */
    public function isProfiling();

    /**
     * Get profiling data
     * @return array[]
     */
    public function getProfilingData();
}