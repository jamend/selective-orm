<?php
namespace jamend\Selective;

/**
 * Wrap lower-level database access functions like connecting, queries, and fetching
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class DB {
	private $name;
	/**
	 * @var \PDO
	 */
	private $pdo;
	private $tables;
	
	/**
	 * Connect with the specified connection settings
	 * @param string $name
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($name, $host, $username, $password) {
		$this->name = $name;
		$this->pdo = new \PDO("mysql:host={$host};dbname={$name}", $username, $password);
	}

	/**
	 * Get the database name
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Run a query and return the resulting statement
	 * @param string $sql
	 * @param array $params
	 * @throws \Exception
	 * @return \PDOStatement
	 */
	public function query($sql, $params = null) {
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
	public function executeUpdate($sql, $params = null) {
		$stmt = $this->query($sql, $params);
		return $stmt->rowCount();
	}
	
	/**
	 * Get the last auto-increment ID value after an insert statement
	 * @return string
	 */
	public function lastInsertID() {
		return $this->pdo->lastInsertId();
	}
	
	/**
	 * Get an array of all rows of a statement as associative arrays
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public function fetchAll($sql, $params = null) {
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
	public function fetchRow($stmt) {
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}
	
	/**
	 * Get a row from a statement as an object
	 * @param \PDOStatement $stmt
	 * @param string $className Name of class of resulting object
	 * @param string $args Arguments to pass to class constructor
	 */
	public function fetchObject($stmt, $className, $args = null) {
		return $stmt->fetchObject($className, $args);
	}
	
	/**
	 * Get a list of names of the table in the database
	 * @return array
	 */
	public function getTables() {
		// Cache the list of tables
		if (!isset($this->tables)) {
			$this->tables = $this->fetchAll("SHOW TABLES FROM `{$this->name}`");
		}
		return $this->tables;
	}
	
	/**
	 * Quote a value for use in SQL statements
	 * @param mixed $value
	 */
	public static function quote($value) {
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
	 * Get a Table object for the given name
	 * @param String $name
	 * @return \jamend\Selective\Table
	 */
	public function __get($name) {
		// Cache the table
		return $this->$name = new Table($name, $this);
	}
}