<?php
namespace selective\ORM\Driver;

use \selective\ORM\Database;
use \selective\ORM\Driver;
use \selective\ORM\Table;
use \selective\ORM\Column;

/**
 * Abstract lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class SQLite extends Driver
{
    private $file = ':memory:';
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
        if (isset($parameters['file'])) $this->file = $parameters['file'];
    }

    /**
     * Connect to the database
     * @param Database $database
     */
    public function connect(Database $database)
    {
        $this->pdo = new \PDO("sqlite:{$this->file}", null, null);
        $this->executeUpdate("PRAGMA foreign_keys = ON");
    }

    /**
     * Get the full quoted identifier including database name
     * @param Table $table
     * @return string
     */
    public function getTableFullIdentifier(Table $table)
    {
        return $this->getTableBaseIdentifier($table);
    }

    /**
     * Quote an object identifier
     * @param string $objectIdentifier
     * @return string
     */
    public function quoteObjectIdentifier($objectIdentifier)
    {
        return "`{$objectIdentifier}`";
    }

    /**
     * Get the SQL expression representing a value for a column
     * @param Column $column
     * @return string
     */
    public function getColumnSQLExpression(Column $column)
    {
        switch ($column->getType()) {
            case 'date':
            case 'datetime':
            case 'timestamp':
                return "strftime('%s', {$column->getFullIdentifier()}) AS {$column->getName()}";
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
     * Get the MySQL-specific representation of a value for a column
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
                    return date('Y-m-d', $value);
                    break;
                case 'datetime':
                    return date('Y-m-d H:i:s', $value);
                    break;
                default:
                    return $value;
                    break;
            }
        }
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
            $this->tableNames[$database->getName()] = [];
            $tables = $this->fetchAll("SELECT name FROM sqlite_master WHERE name LIKE ?", ["{$database->getPrefix()}%"]);
            $offset = strlen($database->getPrefix());
            foreach ($tables as $row) {
                $this->tableNames[$database->getName()][] = substr(current($row), $offset);
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
        $columns = $this->fetchAll("PRAGMA table_info(`{$database->getPrefix()}{$name}`)");

        if ($columns) {
            $tableClass = $database->getClassMapper()->getClassForTable($name);
            /** @var Table $table */
            $table = new $tableClass($name, $database);

            foreach ($columns as $ordinal => $columnInfo) {
                $column = new Column($table);
                $column
                    ->setName($columnInfo['name'])
                    ->setOrdinal($ordinal)
                    ->setType(strtolower($columnInfo['type']))
                    ->setDefault($columnInfo['dflt_value'])
                    ->setAllowNull($columnInfo['notnull'] === '0')
                    ->setPrimaryKey($columnInfo['pk'] === '1')
                    ->setAutoIncrement(strpos($columnInfo['type'], 'INT') === 0 && $columnInfo['pk'])
                ;

                if ($columnInfo['pk']) {
                    $table->primaryKeys[] = $columnInfo['name'];
                }

                $table->columns[$column->getName()] = $column;
            }

            // enumerate relationships
            $offset = strlen($database->getPrefix());
            $foreignKeys = $this->fetchAll("PRAGMA foreign_key_list(`{$database->getPrefix()}{$name}`)", [], null, 'id');
            foreach ($foreignKeys as $mappings) {
                $localColumns = [];
                $relatedColumns = [];

                $constraintName = null;
                $mapping = null;
                foreach ($mappings as $mapping) {
                    $localColumns[] = $mapping['from'];
                    $relatedColumns[] = $mapping['to'];
                    $constraintName = 'fk_' . $mapping['id'];

                    if (!isset($table->foreignKeys[$mapping['from']])) {
                        // columns can have multiple foreign keys; we can only use one of them
                        $table->foreignKeys[$mapping['from']] = $constraintName;
                    }
                }

                $foreignTableName = substr($mapping['table'], $offset);

                if (!isset($table->relatedTables[$foreignTableName])) {
                    // tables can be related to another table multiple times; we can only use one of them
                    $table->relatedTables[$foreignTableName] = $constraintName;
                }

                $table->constraints[$constraintName] = [
                    'localColumns' => $localColumns,
                    'relatedTable' => $foreignTableName,
                    'relatedColumns' => $relatedColumns
                ];
            }

            return $table;
        } else {
            throw new \Exception('Could not parse table definition');
        }
    }
}