<?php
namespace jamend\Selective\Driver\PDO;

use \jamend\Selective\Database;
use \jamend\Selective\Driver\PDO;
use \jamend\Selective\Table;
use \jamend\Selective\Column;

/**
 * Abstract lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class MySQL extends PDO
{
    const CREATE_TABLE_SQL_COLUMNS_REGEX = '/  `(?<name>[^`]+?)` (?<type>[^\(]+?)(?:\((?<length>[^\)]+)\))?(?: unsigned)?(?: CHARACTER SET [a-z0-9\-_]+)?(?: COLLATE [a-z0-9\-_]+)?(?<allowNull> NOT NULL)?(?: DEFAULT (?<default>.+?))?(?<autoIncrement> AUTO_INCREMENT)? ?(?:COMMENT \'[^\']*\')?,?\s/';
    const CREATE_TABLE_SQL_PRIMARY_KEY_REGEX = '/  PRIMARY KEY \(([^\)]+?)\),?/';
    const CREATE_TABLE_SQL_CONSTRAINT_REGEX = '/  CONSTRAINT `(?P<name>[^`]+?)` FOREIGN KEY \((?P<localColumns>[^)]+?)\) REFERENCES `?(?P<relatedTable>[^`]*?)`? \((?P<relatedColumns>[^)]+?)\)(?: ON DELETE [A-Z]+)?(?: ON UPDATE [A-Z]+)?,?/';

    private $host;
    private $username;
    private $password;

    /**
     * Load connection parameters
     * @param array $parameters
     */
    public function loadParameters($parameters)
    {
        $this->host = $parameters['host'];
        $this->username = $parameters['username'];
        $this->password = $parameters['password'];
    }

    /**
     * Connect to the database
     * @param Database $database
     */
    public function connect(Database $database)
    {
        $this->pdo = new \PDO("mysql:host={$this->host};dbname={$database->getName()}", $this->username, $this->password);
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
                return "UNIX_TIMESTAMP({$column->getBaseIdentifier()}) AS {$column->getName()}";
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
            $tables = $this->fetchAll("SHOW TABLES FROM `{$database->getName()}` LIKE ?", array("{$database->getPrefix()}%"));
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
     * @param string $name
     * @throws \Exception
     * @return Table
     */
    public function getTable(Database $database, $name)
    {
        $createTableInfo = $this->fetchAll("SHOW CREATE TABLE `{$database->getPrefix()}{$name}`");
        $createTableSql = $createTableInfo[0]['Create Table'];
        $columns = array();
        $primaryKeys = array();
        $constraints = array();

        $lowerCaseToActualTableNames = array();
        $actualTableNames = array();
        foreach ($this->getTables($database) as $tableName) {
            $actualTableNames[$tableName] = true;
            $lowerToRealCaseTableNames[strtolower($tableName)] = $tableName;
        }

        // parse columns
        if (preg_match_all(self::CREATE_TABLE_SQL_COLUMNS_REGEX, $createTableSql, $columns, PREG_SET_ORDER)) {
            $table = new Table($name, $database);

            foreach ($columns as $columnInfo) {
                $column = new Column($table);

                $default = null;
                if (isset($columnInfo['default']) && $columnInfo['default'] !== 'NULL') {
                    if ($columnInfo['default'] === '') {
                        $default = '';
                    } else {
                        // we need to parse the SQL default value
                        $defaultResult = $this->fetchAll('SELECT ' . $columnInfo['default']);
                        $default = current(current($defaultResult));
                    }
                }

                $column
                    ->setName($columnInfo['name'])
                    ->setType($columnInfo['type'])
                    ->setDefault($default)
                    ->setAllowNull(!isset($columnInfo['allowNull']) || $columnInfo['allowNull'] === 'NULL')
                    ->setAutoIncrement(!empty($columnInfo['autoIncrement']))
                ;

                if ($column->getType() == 'set' || $column->getType() == 'enum') {
                    // we need to parse the SQL options
                    $optionsResult = $this->fetchAll('SELECT ' . $columnInfo['length']);
                    $options = array();
                    $i = 0;
                    foreach (current($optionsResult) as $option) {
                        $options[($column->getType() == 'set' ? pow(2, $i) : $i)] = $option;
                        $i++;
                    }
                    $column->setOptions($options);
                } else {
                    $column->setLength(isset($columnInfo['length']) && $columnInfo['length'] !== '' ? $columnInfo['length'] : null);
                }

                $table->columns[$column->getName()] = $column;
            }

            // parse primary keys
            preg_match(self::CREATE_TABLE_SQL_PRIMARY_KEY_REGEX, $createTableSql, $primaryKeys);
            unset($primaryKeys[0]);

            foreach ($primaryKeys as $primaryKey) {
                $primaryKey = trim($primaryKey, '`');
                $table->primaryKeys[] = $primaryKey;
                $table->columns[$primaryKey]->setPrimaryKey(true);
            }

            // parse relationships
            preg_match_all(self::CREATE_TABLE_SQL_CONSTRAINT_REGEX, $createTableSql, $constraints, PREG_SET_ORDER);
            $offset = strlen($database->getPrefix());
            foreach ($constraints as $constraint) {
                $localColumns = explode('`, `', trim($constraint['localColumns'], '`'));
                $relatedColumns = explode('`, `', trim($constraint['relatedColumns'], '`'));

                foreach ($localColumns as $localColumn) {
                    if (!isset($table->foreignKeys[$localColumn])) {
                        // columns can have multiple foreign keys; we can only use one of them
                        $table->foreignKeys[$localColumn] = $constraint['name'];
                    }
                }

                $foreignTableName = substr($constraint['relatedTable'], $offset);
                // workaround for http://bugs.mysql.com/bug.php?id=6555
                // map lower case table names to actual case table names
                if (!isset($actualTableNames[$foreignTableName]) && isset($lowerToRealCaseTableNames[$foreignTableName])) {
                    $foreignTableName = $lowerToRealCaseTableNames[$foreignTableName];
                }
                if (!isset($table->relatedTables[$foreignTableName])) {
                    // tables can be related to another table multiple times; we can only use one of them
                    $table->relatedTables[$foreignTableName] = $constraint['name'];
                }

                $table->constraints[$constraint['name']] = array(
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