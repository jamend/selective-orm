<?php
namespace jamend\Selective;

/**
 * Wrap lower-level database access functions like connecting, queries, and fetching
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
abstract class DB
{
    /**
     * @var \PDO
     */
    protected $pdo;
    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * Connect to the database
     */
    abstract protected function connect();

    /**
     * Get the database name
     * @return string
     */
    abstract public function getName();

    /**
     * Get the database table prefix
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the database table prefix
     * @param $prefix string
     */
    public function setPrefix($prefix)
    {
        return $this->prefix = $prefix;
    }

    /**
     * Get the full quoted identifier including database name
     * @param Table $table
     * @return string
     */
    abstract public function getTableFullIdentifier(Table $table);

    /**
     * Get the quoted identifier for the table name
     * @param Table $table
     * @return string
     */
    abstract public function getTableBaseIdentifier(Table $table);

    /**
     * Get the full quoted identifier including database/table name
     * @param Column $column
     * @return string
     */
    abstract public function getColumnFullIdentifier(Column $column);

    /**
     * Get the quoted identifier for the column name
     * @param Column $column
     * @return string
     */
    abstract public function getColumnBaseIdentifier(Column $column);

    /**
     * Get the SQL expression to get the normalized value for a column
     * @param Column $column
     * @return string
     */
    abstract public function getColumnSQLExpression(Column $column);

    /**
     * Get the implementation-specific representation of a value for a column
     * @param Column $column
     * @param mixed $value
     * @return mixed
     */
    abstract public function getColumnDenormalizedValue(Column $column, $value);

    /**
     * Load a DB of the given type and parameters
     * @param string $type DB class name
     * @param array $parameters DB class-specific parameters
     * @return \jamend\Selective\DB
     */
    public static function loadDB($type, $parameters)
    {
        if ($type{0} === '\\') {
            // db class has absolute namespace
            $dbClass = $type;
        } else {
            // db class is relative to this namespace
            $dbClass = "\jamend\Selective\DB\\{$type}";
        }
        $db = new $dbClass();
        foreach ($parameters as $name => $value) {
            $setter = 'set' . ucfirst($name);
            call_user_func_array(array($db, $setter), array($value));
        }
        $db->connect();
        return $db;
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

        // Execute and check if there was an error
        if ($stmt->execute($params)) {
            return $stmt;
        } else {
            $errorInfo = $stmt->errorInfo();
            throw new \Exception('Query failed: "' . $sql . '" (' . $errorInfo[1] . ': ' . $errorInfo[2] . ')');
        }
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
     * Get the last auto-increment ID value after an insert statement
     * @return string
     */
    public function lastInsertID()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get an array of all rows of a statement as associative arrays
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll($sql, $params = null)
    {
        $stmt = $this->query($sql, $params);
        $rows = array();
        if ($stmt) {
            while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Get a row from a statement as an associative array
     * @param \PDOStatement $stmt
     */
    public function fetchRow($stmt)
    {
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get a row from a statement as an object
     * @param \PDOStatement $stmt
     * @param string $className Name of class of resulting object
     * @param string $args Arguments to pass to class constructor
     */
    public function fetchObject($stmt, $className, $args = null)
    {
        return $stmt->fetchObject($className, $args);
    }

    /**
     * Get a list of names of the table in the database
     * @return array
     */
    abstract public function getTables();

    /**
     * Checks if a table exists
     * @param string $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        return in_array($tableName, $this->getTables());
    }

    /**
     * Quote a value for use in SQL statements
     * @param mixed $value
     */
    abstract public function quote($value);

    /**
     * Get a Table by name
     * @param string $name
     * @return Table
     */
    abstract public function getTable($name);

    /**
     * Get a Table object for the given name
     * @param String $name
     * @return \jamend\Selective\Table
     */
    public function __get($name)
    {
        // Cache the table
        return $this->$name = $this->getTable($name);
    }
}