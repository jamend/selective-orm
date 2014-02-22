<?php
namespace jamend\ORM\DB;

/**
 * Wrap lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class PDOMySQL extends \jamend\Selective\DB {
	private $dbname;
	private $host;
	private $username;
	private $password;
	protected $tables;
	
	public function setDbname($dbname) {
		$this->dbname = $dbname;
	}
	
	public function setHost($host) {
		$this->host = $host;
	}
	
	public function setUsername($username) {
		$this->username = $username;
	}
	
	public function setPassword($password) {
		$this->password = $password;
	}
	
	/**
	 * Connect to the database
	 */
	protected function connect() {
		$this->pdo = new \PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
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
	public function quote($value) {
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
}