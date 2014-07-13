<?php
namespace selective\ORM\Driver;

use \selective\ORM\Database;
use \selective\ORM\Driver;
use \selective\ORM\Table;
use \selective\ORM\Column;
use \selective\ORM\Query;

/**
 * Abstract lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Sqlsrv extends Driver
{
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    private $schema = 'dbo';
    /**
     * @var string[]
     */
    private $tableNames;

    /**
     * Load connection parameters
     * @param array $parameters
     */
    public function loadParameters($parameters)
    {
        $this->host = $parameters['host'];
        $this->username = $parameters['username'];
        $this->password = $parameters['password'];
        if (isset($parameters['schema'])) {
            $this->schema = $parameters['schema'];
        }
    }

    /**
     * Connect to the database
     * @param Database $database
     */
    public function connect(Database $database)
    {
        $this->pdo = new \PDO("sqlsrv:Server={$this->host};Database={$database->getName()}", $this->username, $this->password);
    }

    /**
     * Get the full quoted identifier including database name
     * @param Table $table
     * @return string
     */
    public function getTableFullIdentifier(Table $table)
    {
        return "[{$table->getDatabase()->getName()}].[{$this->schema}].{$this->getTableBaseIdentifier($table)}";
    }

    /**
     * Quote an object identifier
     * @param string $objectIdentifier
     * @return string
     */
    public function quoteObjectIdentifier($objectIdentifier)
    {
        return "[{$objectIdentifier}]";
    }

    /**
     * Get the MySQL-specific representation of a value for a column
     * @param Column $column
     * @return string
     */
    public function getColumnSQLExpression(Column $column)
    {
        switch ($column->getType()) {
            case 'date':
            case 'datetime':
                return "DATEDIFF(SECOND,{d '1970-01-01'}, {$column->getFullIdentifier()}) AS {$column->getName()}";
                break;
            case 'set':
                return "{$column->getFullIdentifier()} + 0 AS {$column->getName()}";
                break;
            default:
                return $column->getFullIdentifier();
                break;
        }
    }

    /**
     * Get the SQL expression representing a value for a column
     * @param Column $column
     * @param mixed $value
     * @return mixed
     */
    public function getColumnDenormalizedValue(Column $column, $value)
    {
        if ($value === null) {
            return null;
        } else {
            switch ($column->getType()) {
                case 'date':
                    return date('Ymd', $value);
                    break;
                case 'datetime':
                    return date('Y-m-d\TH:i:s', $value);
                    break;
                default:
                    return $value;
                    break;
            }
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

        if ($limitClause = $query->getLimit()) {
            if (empty($limitClause[1])) {
                $sql = "SELECT TOP {$limitClause[0]} {$columns} FROM {$from}{$where}{$orderBy}";
            } else {
                $primaryKeys = '';
                $outerColumns = '';
                foreach ($table->getColumns() as $column) {
                    if ($column->isPrimaryKey()) {
                        $primaryKeys .= ", {$column->getSQLExpression()}";
                    }
                    $outerColumns .= ", {$column->getBaseIdentifier()}";
                }
                $primaryKeys = substr($primaryKeys, 2); // remove first ', '
                $outerColumns = substr($outerColumns, 2); // remove first ', '
                $data = "SELECT {$columns}, ROW_NUMBER() OVER (ORDER BY {$primaryKeys}) AS [_rowCount] FROM {$from}{$where}{$orderBy}";

                $to = $limitClause[0] + $limitClause[1];
                $sql = <<<SQL
;WITH DATA AS (
	{$data}
)
SELECT {$outerColumns}
FROM DATA
WHERE [_rowCount] BETWEEN {$limitClause[1]} AND {$to}
{$orderBy}
SQL;
            }
        } else {
            $sql = "SELECT {$columns} FROM {$from}{$where}{$orderBy}";
        }

        return $sql;
    }

    /**
     * Get a list of names of the table in a database
     * @param Database $database
     * @return string[]
     */
    public function getTables(Database $database)
    {
        // Cache the list of tables
        if (!isset($this->tableNames[$database->getName()])) {
            $this->tableNames[$database->getName()] = array();
            $tables = $this->fetchAll("SELECT name, object_id FROM sys.objects WHERE type IN ('U ', 'V ')", array(), 'object_id');
            $offset = strlen($database->getPrefix());
            foreach ($tables as $index => $row) {
                $this->tableNames[$database->getName()][$index] = substr(current($row), $offset);
            }
        }
        return $this->tableNames[$database->getName()];
    }

    /**
     * Get a Table object for the given name
     * TODO table/column properties should not be public
     * @param Database $database
     * @param String $name
     * @throws \Exception
     * @return Table
     */
    public function buildTable(Database $database, $name)
    {
        $objectInfo = $this->fetchAll("SELECT object_id FROM sys.objects WHERE type IN ('U ', 'V ') AND name = ?", array($name));
        if (isset($objectInfo[0]['object_id'])) {
            $objectId = $objectInfo[0]['object_id'];

            $columns = $this->fetchAll(<<<SQL
SELECT
	columns.name,
	types.name AS type,
	columns.max_length AS length,
	columns.is_nullable AS allowNull,
	default_constraints.definition AS [default],
	columns.is_identity AS isAutoIncrement,
	COALESCE(indexes.is_primary_key, 0) AS isPrimaryKey
FROM
	sys.columns
	INNER JOIN sys.types ON columns.user_type_id = types.user_type_id
	LEFT JOIN sys.default_constraints
		ON columns.default_object_id = default_constraints.object_id
		AND columns.object_id = default_constraints.parent_object_id
	LEFT JOIN sys.index_columns
		ON index_columns.column_id = columns.column_id
		AND index_columns.object_id = columns.object_id
	LEFT JOIN sys.indexes
		ON indexes.index_id = index_columns.index_id
		AND indexes.object_id = columns.object_id
		AND indexes.is_primary_key = 1
WHERE
	columns.object_id = ?
SQL
                ,
                array($objectId)
            );

            $constraints = $this->fetchAll(<<<SQL
SELECT
	foreign_keys.name constraintName,
	localColumns.name AS localColumnName,
	foreignTables.name AS foreignTableName,
	foreignColumns.name AS foreignColumnName
FROM
	sys.foreign_keys
	INNER JOIN sys.foreign_key_columns ON foreign_keys.object_id = foreign_key_columns.constraint_object_id
	INNER JOIN sys.objects AS foreignTables ON foreignTables.object_id = foreign_key_columns.referenced_object_id
	INNER JOIN sys.columns AS localColumns
		ON localColumns.column_id = foreign_key_columns.parent_column_id
		AND localColumns.object_id = foreign_key_columns.parent_object_id
	INNER JOIN sys.columns AS foreignColumns
		ON foreignColumns.column_id = foreign_key_columns.referenced_column_id
		AND foreignColumns.object_id = foreign_key_columns.referenced_object_id
WHERE
	foreign_keys.parent_object_id = ?
ORDER BY
	foreign_keys.parent_object_id,
	foreign_keys.object_id
SQL
                ,
                array($objectId),
                null,
                'constraintName'
            );

            $tableClass = $database->getClassMapper()->getClassForTable($name);
            /** @var Table $table */
            $table = new $tableClass($name, $database);

            foreach ($columns as $ordinal => $columnInfo) {
                $column = new Column($table);

                $default = null;
                if ($columnInfo['default'] !== null) {
                    // we need to parse the SQL default value
                    $defaultResult = $this->fetchAll('SELECT ' . $columnInfo['default']);
                    $default = current(current($defaultResult));
                }

                $column
                    ->setName($columnInfo['name'])
                    ->setOrdinal($ordinal)
                    ->setType($columnInfo['type'])
                    ->setDefault($default)
                    ->setAllowNull((bool) $columnInfo['allowNull'])
                    ->setPrimaryKey((bool) $columnInfo['isPrimaryKey'])
                    ->setAutoIncrement((bool) $columnInfo['isAutoIncrement'])
                ;

                if ($columnInfo['isPrimaryKey']) {
                    $table->primaryKeys[] = $columnInfo['name'];
                }

                $length = null;

                switch ($columnInfo['type']) {
                	case 'text':
                	    $length = 2147483647;
                	    break;
                	case 'ntext':
                	    $length = 1073741823;
                	    break;
                	case 'varchar':
            	    case 'nvarchar':
                	case 'char':
            	    case 'nchar':
            	        $length = $columnInfo['length'];
                	    break;
                }
                $column->setLength($length);

                $table->columns[$column->getName()] = $column;
            }

            // enumerate relationships
            $offset = strlen($database->getPrefix());
            foreach ($constraints as $constraintName => $mappings) {
                $localColumns = array();
                $relatedColumns = array();

                $mapping = null;
                foreach ($mappings as $mapping) {
                    $localColumns[] = $mapping['localColumnName'];
                    $relatedColumns[] = $mapping['foreignColumnName'];

                    if (!isset($table->foreignKeys[$mapping['localColumnName']])) {
                        // columns can have multiple foreign keys; we can only use one of them
                        $table->foreignKeys[$mapping['localColumnName']] = $constraintName;
                    }
                }

                $foreignTableName = substr($mapping['foreignTableName'], $offset);

                if (!isset($table->relatedTables[$foreignTableName])) {
                    // tables can be related to another table multiple times; we can only use one of them
                    $table->relatedTables[$foreignTableName] = $constraintName;
                }

                $table->constraints[$constraintName] = array(
                    'localColumns' => $localColumns,
                    'relatedTable' => $foreignTableName,
                    'relatedColumns' => $relatedColumns
                );
            }

            return $table;
        } else {
            throw new \Exception('Could not find table ' . $name);
        }
    }
}