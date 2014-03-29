<?php
namespace jamend\Selective\Driver\PDO;

use \jamend\Selective\Database;
use \jamend\Selective\Driver;
use \jamend\Selective\Driver\PDO;
use \jamend\Selective\Table;
use \jamend\Selective\Column;

/**
 * Abstract lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class SQLite extends PDO
{
    private $file = ':memory:';

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
     * Get the MySQL-specific representation of a value for a column
     * @param Column $column
     * @return string
     */
    public function getColumnSQLExpression(Column $column)
    {
        switch ($column->getType()) {
            case 'date':
            case 'datetime':
                return "TIME_TO_SEC(TIMEDIFF({$column->getBaseIdentifier()}, '1970-01-01 00:00:00')) AS {$column->getName()}";
                break;
            case 'set':
                return "{$column->getBaseIdentifier()} + 0 AS {$column->getName()}";
                break;
            default:
                return $column->getBaseIdentifier();
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
        if (!isset($this->tables[$database->getName()])) {
            $this->tables[$database->getName()] = array();
            $tables = $this->fetchAll("SELECT name FROM sqlite_master WHERE name LIKE ?", array("{$database->getPrefix()}%"));
            $offset = strlen($database->getPrefix());
            foreach ($tables as $row) {
                $this->tables[$database->getName()][] = substr(current($row), $offset);
            }
        }
        return $this->tables[$database->getName()];
    }

    /**
     * Get a Table object for the given name
     * TODO table/column properties should not be public
     * @param Database $database
     * @param String $name
     * @throws \Exception
     * @return Table
     */
    public function getTable(Database $database, $name)
    {
        $columns = $this->fetchAll("PRAGMA table_info(`{$database->getPrefix()}{$name}`)");

        if ($columns) {
            $table = new Table($name, $database);

            foreach ($columns as $columnInfo) {
                $column = new Column($table);
                $column
                    ->setName($columnInfo['name'])
                    ->setType($columnInfo['type'])
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
            $foreignKeys = $this->fetchAll("PRAGMA foreign_key_list(`{$database->getPrefix()}{$name}`)", array(), null, 'id');
            foreach ($foreignKeys as $mappings) {
                $localColumns = array();
                $relatedColumns = array();

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

                $table->constraints[$constraintName] = array(
                    'localColumns' => $localColumns,
                    'relatedTable' => $foreignTableName,
                    'relatedColumns' => $relatedColumns
                );
            }

            return $table;
        } else {
            throw new \Exception('Could not parse table definition');
        }
    }
}