<?php
namespace jamend\Selective\Driver;

/**
 * Abstract lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
abstract class PDO implements \jamend\Selective\Driver
{
    /**
     * @var \PDO
     */
    protected $pdo;
    protected $tables = array();

    /**
     * Get the full quoted identifier including database name
     * @param \jamend\Selective\Table $table
     * @return string
     */
    public function getTableFullIdentifier(\jamend\Selective\Table $table)
    {
        return "{$this->quoteObjectIdentifier($table->getDatabase()->getName())}.{$this->getTableBaseIdentifier($table)}";
    }

    /**
     * Get the quoted identifier for the table name
     * @param Table $table
     * @return string
     */
    public function getTableBaseIdentifier(\jamend\Selective\Table $table)
    {
        return $this->quoteObjectIdentifier($table->getDatabase()->getPrefix() . $table->getName());
    }

    /**
     * Get the full quoted identifier including database/table name
     * @param Column $column
     * @return string
     */
    public function getColumnFullIdentifier(\jamend\Selective\Column $column)
    {
        return "{$this->getTableFullIdentifier($column->getTable())}.{$this->getColumnBaseIdentifier($column)}";
    }

    /**
     * Get the quoted identifier for the column name
     * @param Column $column
     * @return string
     */
    public function getColumnBaseIdentifier(\jamend\Selective\Column $column)
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
     * Quote a value for use in SQL statements
     * @param mixed $value
     */
    public function quote($value)
    {
        if ($value === null) {
            return 'null';
        } else if (is_bool($value)) {
            return $value ? 1 : 0;
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
    protected function query($sql, $params = null)
    {
        $stmt = $this->pdo->prepare($sql);

        // Execute and check if there was an error
        if ($stmt) {
            if ($stmt->execute($params)) {
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
     * Get a row from a statement as an associative array
     * @param \PDOStatement $stmt
     */
    protected function fetchRow($stmt)
    {
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get a row from a statement as an object
     * @param \PDOStatement $stmt
     * @param string $className Name of class of resulting object
     * @param string $args Arguments to pass to class constructor
     */
    protected function fetchObject($stmt, $className, $args = null)
    {
        return $stmt->fetchObject($className, $args);
    }

    /**
     * Build SQL column list
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @param unknown $params
     * @return string
     */
    protected function buildColumnList(\jamend\Selective\Table $table, \jamend\Selective\Query $query, &$params)
    {
        $columns = '';
        foreach ($table->getColumns() as $columnName => $column) {
            $columns .= ", {$column->getSQLExpression()}";
        }
        return substr($columns, 2); // remove first ', '
    }

    /**
     * Build SQL WHERE clause
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @param unknown $params
     * @return string
     */
    protected function buildWhereClause(\jamend\Selective\Table $table, \jamend\Selective\Query $query, &$params)
    {
        $where = '';
        foreach ($query->getWhere() as $condition) {
            $where .= ' AND (' . $condition[0] . ')';
            if (!empty($condition[1])) $params = array_merge($params, $condition[1]);
        }
        if ($where) {
            return ' WHERE ' . substr($where, 5); // replace first AND with WHERE
        } else {
            return '';
        }
    }

    /**
     * Build SQL HAVING clause
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @param unknown $params
     * @return string
     */
    protected function buildHavingClause(\jamend\Selective\Table $table, \jamend\Selective\Query $query, &$params)
    {
        $having = '';
        foreach ($query->getHaving() as $havingClause) {
            $having .= ' AND (' . $havingClause[0] . ')';
            if (!empty($havingClause[1])) $params = array_merge($params, $havingClause[1]);
        }
        if ($having) {
            return ' HAVING ' . substr($having, 5); // replace first AND with HAVING
        } else {
            return '';
        }
    }

    /**
     * Build SQL ORDER BY clause
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @param &array $params
     * @return string
     */
    protected function buildOrderByClause(\jamend\Selective\Table $table, \jamend\Selective\Query $query, &$params)
    {
        $orderBy = '';
        foreach ($query->getOrderBy() as $fieldAndDirection) {
            $orderBy .= ', ' . $fieldAndDirection[0] . ' ' . $fieldAndDirection[1];
        }
        if ($orderBy) {
            $orderBy = ' ORDER BY ' . substr($orderBy, 2); // remove first ", "
        } else {
            return '';
        }
    }

    /**
     * Build SQL LIMIT clause
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @param &array $params
     * @return string
     */
    protected function buildLimitClause(\jamend\Selective\Table $table, \jamend\Selective\Query $query, &$params)
    {
        if ($limitClause = $query->getLimit()) {
            return ' LIMIT ' . $limitClause[1] . ', ' . $limitClause[0];
        } else {
            return '';
        }
    }

    /**
     * Generate the SQL query to get a Table's records for the given Query
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @param &array $params
     */
    public function buildSQL(\jamend\Selective\Table $table, \jamend\Selective\Query $query, &$params)
    {
        $columns = $this->buildColumnList($table, $query, $params);
        $where = $this->buildWhereClause($table, $query, $params);
        $having = $this->buildHavingClause($table, $query, $params);
        $orderBy = $this->buildOrderByClause($table, $query, $params);
        $limit = $this->buildLimitClause($table, $query, $params);

        // assemble query
        return "SELECT {$columns} FROM {$table->getFullIdentifier()}{$where}{$having}{$orderBy}{$limit}";
    }

    /**
     * Get a Table's records for the given Query
     * @param \jamend\Selective\Table $table
     * @param \jamend\Selective\Query $query
     * @return \jamend\Selective\Record[]
     */
    public function getRecords(\jamend\Selective\Table $table, \jamend\Selective\Query $query)
    {
        $params = array();

        $sql = $this->buildSQL($table, $query, $params);

        $result = $this->query($sql, $params);

        $records = array();
        while ($record = $this->fetchObject($result, 'jamend\Selective\Record', array($table))) {
            $records[$record->getID()] = $record;
        }
        return $records;
    }

    /**
     * Get the WHERE clause to identify a record by its primary key values
     * @param \jamend\Selective\Record $record
     * @param &array $params array will to which prepared statement bind
     *     parameters will be added
     */
    protected function getRecordIdentifyingWhereClause(\jamend\Selective\Record $record, &$params)
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
     * @param \jamend\Selective\Record $record
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
            $params[] = $column->getColumnDenormalizedValue($record->{$columnName});
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
     * @param \jamend\Selective\Record $record
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
            $params[] = $column->getColumnDenormalizedValue($record->{$columnName});
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
     * @param \jamend\Selective\Record $record
     * @return boolean True if the record is deleted
     */
    public function deleteRecord($record)
    {
        $params = array();
        $keyCriteria = $this->getRecordIdentifyingWhereClause($record, $params);
        return $this->executeUpdate("DELETE FROM {$record->getTable()->getFullIdentifier()} WHERE {$keyCriteria}", $params);
    }
}