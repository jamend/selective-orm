<?php
namespace selective\ORM\Driver;

use \selective\ORM\Database;
use \selective\ORM\Driver;
use \selective\ORM\Table;
use \selective\ORM\Record;
use \selective\ORM\Column;
use \selective\ORM\Query;
use \selective\ORM\Hydrator;

/**
 * Abstract lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
abstract class PDO implements Driver
{
    /**
     * @var \PDO
     */
    protected $pdo;
    private $tables = [];
    private $profiling = false;
    private $profilingData = [];

    /**
     * Get the full quoted identifier including database name
     * @param Table $table
     * @return string
     */
    public function getTableFullIdentifier(Table $table)
    {
        return "{$this->quoteObjectIdentifier($table->getDatabase()->getName())}.{$this->getTableBaseIdentifier($table)}";
    }

    /**
     * Get the quoted identifier for the table name
     * @param Table $table
     * @return string
     */
    public function getTableBaseIdentifier(Table $table)
    {
        return $this->quoteObjectIdentifier($table->getDatabase()->getPrefix() . $table->getName());
    }

    /**
     * Get the full quoted identifier including database/table name
     * @param Column $column
     * @return string
     */
    public function getColumnFullIdentifier(Column $column)
    {
        return "{$this->getTableFullIdentifier($column->getTable())}.{$this->getColumnBaseIdentifier($column)}";
    }

    /**
     * Get the quoted identifier for the column name
     * @param Column $column
     * @return string
     */
    public function getColumnBaseIdentifier(Column $column)
    {
        return $this->quoteObjectIdentifier($column->getName());
    }

    /**
     * Quote an object identifier
     * @param string $objectIdentifier
     * @return string
     */
    public abstract function quoteObjectIdentifier($objectIdentifier);

    /**
     * Build a selective\ORM\Table by name
     * @param Database $database
     * @param string $name
     * @return string
     */
    public abstract function buildTable(Database $database, $name);

    /**
     * Get a selective\ORM\Table by name
     * @param string $name
     * @param Database $database
     * @return Table
     */
    public function getTable(Database $database, $name)
    {
        if (!isset($this->tables[$database->getName()][$name])) {
            $this->tables[$database->getName()][$name] = $this->buildTable($database, $name);
        }

        return $this->tables[$database->getName()][$name];
    }

    /**
     * Quote a value for use in SQL statements
     * @param mixed $value
     * @return string
     */
    public function quote($value)
    {
        if ($value === null) {
            return 'null';
        } else if (is_bool($value)) {
            return $value ? '1' : '0';
        } else if (is_numeric($value) && $value === strval(intval($value))) {
            return intval($value);
        } else {
            return '"' . addslashes($value) . '"';
        }
    }

    /**
     * Get the last auto-increment ID value after an insert statement
     * @return string
     */
    protected function lastInsertID()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get an array of all rows of a statement as associative arrays
     * @param string $sql
     * @param array $params
     * @param string $indexField
     * @param string $groupField
     * @return array[]
     */
    public function fetchAll($sql, $params = array(), $indexField = null, $groupField = null)
    {
        $stmt = $this->query($sql, $params);
        $rows = array();
        if ($stmt) {
            if ($groupField === null) {
                if ($indexField === null) {
                    while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                        $rows[] = $row;
                    }
                } else {
                    while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                        $rows[$row[$indexField]] = $row;
                    }
                }
            } else {
                if ($indexField === null) {
                    while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                        $rows[$row[$groupField]][] = $row;
                    }
                } else {
                    while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                        $rows[$row[$groupField]][$row[$indexField]] = $row;
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * Run a query and return the resulting statement
     * @param string $sql
     * @param array $params
     * @throws \Exception
     * @return \PDOStatement
     */
    public function query($sql, $params = null)
    {
        $stmt = $this->pdo->prepare($sql);

        $startTime = 0;
        if ($this->isProfiling()) {
            $startTime = microtime(true);
        }

        // Execute and check if there was an error
        if ($stmt) {
            if ($stmt->execute($params)) {
                if ($this->isProfiling()) {
                    $executionTime = microtime(true) - $startTime;
                    $this->profilingData[] = [
                        'time' => $executionTime,
                        'sql' => $sql
                    ];
                }
                return $stmt;
            } else {
                $errorInfo = $stmt->errorInfo();
            }
        } else {
            $errorInfo = $this->pdo->errorInfo();
        }

        $errorMessage = "Query failed - SQLSTATE[{$errorInfo[0]}]";
        if (isset($errorInfo[1])) {
            $errorMessage .= " ({$errorInfo[1]}: {$errorInfo[2]})";
        }
        $errorMessage .= "; SQL: \"{$sql}\"";
        throw new \Exception($errorMessage);
    }

    /**
     * Execute an update query and return the number of affected rows
     * @param string $sql
     * @param array $params
     * @throws \Exception
     * @return number of affected rows
     */
    public function executeUpdate($sql, $params = null)
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Build SQL column list
     * @param Table $table
     * @return string
     */
    protected function buildColumnList(Table $table)
    {
        $columns = '';
        foreach ($table->getColumns() as $column) {
            $columns .= ",\n\t{$column->getSQLExpression()}";
        }
        return substr($columns, 3); // remove first ', '
    }

    /**
     * Build SQL FROM clause
     * @param Query $query
     * @param Table $table
     * @param string &$columns
     * @return string
     */
    protected function buildFromClause(Query $query, Table $table, &$columns)
    {
        $from = $table->getFullIdentifier();
        foreach ($query->getJoins() as $join) {
            if ($join['type'] == 'left') {
                $from .= "\n\tLEFT JOIN ";
            } else {
                $from .= "\n\tINNER JOIN ";
            }
            $rightSideTable = $table->getDatabase()->getTable($join['table']);
            if (!empty($join['columns'])) {
                foreach ($join['columns'] as $columnName) {
                    $column = $rightSideTable->getColumn($columnName);
                    $columns .= ",\n\t{$column->getSQLExpression()}";
                }
            }
            $from .= $rightSideTable->getFullIdentifier();
            if (empty($join['alias'])) {
                $rightSideTableReference = $rightSideTable->getFullIdentifier();
            } else {
                $rightSideTableReference = $join['alias'];
                $from .= ' AS ' . $join['alias'];
            }
            $on = '';
            foreach ($join['on'] as $leftSide => $rightSide) {
                $on .= ' AND ' . $table->getFullIdentifier() . '.' . $leftSide . ' = ' . $rightSideTableReference . '.' . $rightSide;
            }
            $from .= "\n\t\tON (" . substr($on, 5) . ')';
        }
        return $from;
    }

    /**
     * Build SQL WHERE clause
     * @param Query $query
     * @param &array $params
     * @return string
     */
    protected function buildWhereClause(Query $query, &$params)
    {
        $where = '';
        foreach ($query->getWhere() as $condition) {
            $where .= "\n\tAND (" . $condition[0] . ')';
            if (!empty($condition[1])) $params = array_merge($params, $condition[1]);
        }
        if ($where) {
            return "\nWHERE\n\t" . substr($where, 6); // replace first AND with WHERE
        } else {
            return '';
        }
    }

    /**
     * Build SQL ORDER BY clause
     * @param Query $query
     * @return string
     */
    protected function buildOrderByClause(Query $query)
    {
        $orderBy = '';
        foreach ($query->getOrderBy() as $fieldAndDirection) {
            $orderBy .= ",\n\t" . $fieldAndDirection[0] . ' ' . $fieldAndDirection[1];
        }
        if ($orderBy) {
            return "\nORDER BY\n\t" . substr($orderBy, 3); // remove first ", "
        } else {
            return '';
        }
    }

    /**
     * Build SQL LIMIT clause
     * @param Query $query
     * @return string
     */
    protected function buildLimitClause(Query $query)
    {
        if ($limitClause = $query->getLimit()) {
            return "\nLIMIT " . $limitClause[1] . ', ' . $limitClause[0];
        } else {
            return '';
        }
    }

    /**
     * Generate the SQL query to get a Table's records for the given Query
     * @param Table $table
     * @param Query $query
     * @param &array $params
     * @return string
     */
    public function buildSQL(Table $table, Query $query, &$params)
    {
        $columns = $this->buildColumnList($table);
        $from = $this->buildFromClause($query, $table, $columns);
        $where = $this->buildWhereClause($query, $params);
        $orderBy = $this->buildOrderByClause($query);
        $limit = $this->buildLimitClause($query);

        // assemble query
        return "SELECT\n\t{$columns}\nFROM\n\t{$from}{$where}{$orderBy}{$limit}";
    }

    /**
     * Get a Table's records for the given Query
     * @param Table $table
     * @param Query $query
     * @param bool $asArray return the records as arrays instead of objects
     * @return Hydrator
     */
    public function getHydrator(Table $table, Query $query, $asArray = false)
    {
        return new PDO\Hydrator($this, $table, $query, $asArray);
    }

    /**
     * Get the WHERE clause to identify a record by its primary key values
     * @param Record $record
     * @param &array $params array will to which prepared statement bind
     *     parameters will be added
     * @return string
     */
    protected function getRecordIdentifyingWhereClause(Record $record, &$params)
    {
        $keyCriteria = '';
        foreach ($record->getTable()->getPrimaryKeys() as $columnName) {
            $column = $record->getTable()->getColumns()[$columnName];
            $keyCriteria .= " AND {$column->getBaseIdentifier()} = ?";
            $params[] = $record->{$columnName};
        }
        return substr($keyCriteria, 5); // remove first ' AND '
    }

    /**
     * Update an existing record in the database
     * @param Record $record
     * @return int number of affected rows, or false
     */
    public function updateRecord($record)
    {
        $params = array();

        // Build an update query for an existing record
        $update = '';
        foreach ($record->getTable()->getColumns() as $columnName => $column) {
            if ($column->isAutoIncrement()) continue;
            $update .= ", {$column->getBaseIdentifier()} = ?";
            $params[] = $column->getColumnDenormalizedValue($record->getRawPropertyValue($columnName));
        }
        $update = substr($update, 2); // remove first ', '

        $keyCriteria = $this->getRecordIdentifyingWhereClause($record, $params);

        return $this->executeUpdate(
            "UPDATE {$record->getTable()->getFullIdentifier()} SET {$update} WHERE {$keyCriteria}",
            $params
        );
    }

    /**
     * Insert a record into the database
     * @param Record $record
     * @return int number of affected rows, or false
     */
    public function insertRecord($record)
    {
        $params = array();

        // Build an insert query for a new record
        $fields = '';
        $values = '';
        $autoIncrementColumn = null;
        foreach ($record->getTable()->getColumns() as $columnName => $column) {
            if ($column->isAutoIncrement()) {
                $autoIncrementColumn = $columnName;
                continue;
            }
            $fields .= ", {$column->getBaseIdentifier()}";
            $values .= ', ?';
            $params[] = $column->getColumnDenormalizedValue($record->getRawPropertyValue($columnName));
        }
        $fields = substr($fields, 2); // remove first ', '
        $values = substr($values, 2); // remove first ', '

        $result = $this->executeUpdate(
            "INSERT INTO {$record->getTable()->getFullIdentifier()} ({$fields}) VALUES ({$values})",
            $params
        );

        if ($result && $autoIncrementColumn) {
            $record->{$autoIncrementColumn} = $this->lastInsertID();
        }

        return $result;
    }

    /**
     * Delete a record from the database
     * @param Record $record
     * @return boolean True if the record is deleted
     */
    public function deleteRecord($record)
    {
        $params = array();
        $keyCriteria = $this->getRecordIdentifyingWhereClause($record, $params);
        return $this->executeUpdate("DELETE FROM {$record->getTable()->getFullIdentifier()} WHERE {$keyCriteria}", $params);
    }

    /**
     * Enable/disable profiling
     * @param bool $profiling
     */
    public function setProfiling($profiling)
    {
        $this->profiling = $profiling;
    }

    /**
     * Check if profiling is enabled
     * @return bool
     */
    public function isProfiling()
    {
        return $this->profiling;
    }

    /**
     * Get profiling data
     * @return array[]
     */
    public function getProfilingData()
    {
        $profilingData = $this->profilingData;
        $total = 0;
        foreach ($this->profilingData as $query) {
            $total += $query['time'];
        }
        $profilingData['total'] = ['time' => $total, 'sql' => count($this->profilingData) . ' queries'];
        return $profilingData;
    }
}