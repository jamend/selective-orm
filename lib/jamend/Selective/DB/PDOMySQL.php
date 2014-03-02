<?php
namespace jamend\Selective\DB;

/**
 * Wrap lower-level database access functions like connecting, queries, and
 * fetching results
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class PDOMySQL extends \jamend\Selective\DB {
	const CREATE_TABLE_SQL_COLUMNS_REGEX = '/  `(?<name>[^`]+?)` (?<type>[^\(]+?)(?:\((?<length>[^\)]+)\))?(?: unsigned)?(?: CHARACTER SET [a-z0-9\-_]+)?(?: COLLATE [a-z0-9\-_]+)?(?<allowNull> NOT NULL)?(?: DEFAULT (?<default>.+?))?(?: AUTO_INCREMENT)? ?(?:COMMENT \'[^\']*\')?,?\s/';
	const CREATE_TABLE_SQL_PRIMARY_KEY_REGEX = '/  PRIMARY KEY \(([^\)]+?)\),?/';
	const CREATE_TABLE_SQL_CONSTRAINT_REGEX = '/  CONSTRAINT `(?P<name>[^`]+?)` FOREIGN KEY \((?P<localColumns>[^)]+?)\) REFERENCES `?(?P<relatedTable>[^`]*?)`? \((?P<relatedColumns>[^)]+?)\)(?: ON DELETE [A-Z]+)?(?: ON UPDATE [A-Z]+)?,?/';
	
	private $dbname;
	private $host;
	private $username;
	private $password;
	protected $tables;
	
	/**
	 * 
	 * @param string $dbname
	 */
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
	 * Get the database name
	 * @return string
	 */
	public function getName() {
		return $this->dbname;
	}
	
	/**
	 * Get the full quoted identifier including database name
	 * @param Table $table
	 * @return string
	 */
	public function getTableFullIdentifier(\jamend\Selective\Table $table) {
		return "`{$this->getName()}`.{$this->getTableBaseIdentifier($table)}";
	}
	
	/**
	 * Get the quoted identifier for the table name
	 * @param Table $table
	 * @return string
	 */
	public function getTableBaseIdentifier(\jamend\Selective\Table $table) {
		return "`{$table->getName()}`";
	}
	
	/**
	 * Get the full quoted identifier including database/table name
	 * @param Column $column
	 * @return string
	 */
	public function getColumnFullIdentifier(\jamend\Selective\Column $column) {
		return "{$this->getTableFullIdentifier($column->getTable())}.{$this->getColumnBaseIdentifier($column)}";
	}
	
	/**
	 * Get the quoted identifier for the column name
	 * @param Column $column
	 * @return string
	 */
	public function getColumnBaseIdentifier(\jamend\Selective\Column $column) {
		return "`{$column->getName()}`";
	}
	
	/**
	 * Get a list of names of the table in the database
	 * @return array
	 */
	public function getTables() {
		// Cache the list of tables
		if (!isset($this->tables)) {
			$this->tables = array();
			$tables = $this->fetchAll("SHOW TABLES FROM `{$this->dbname}`");
			foreach ($tables as $row) {
				$this->tables[] = current($row);
			}
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
	
	/**
	 * Get a Table object for the given name
	 * TODO table/column properties should not be public
	 * @param String $name
	 * @return \jamend\Selective\Table
	 */
	public function getTable($name) {
		$createTableInfo = $this->fetchAll('SHOW CREATE TABLE ' . $name);
		$createTableSql = $createTableInfo[0]['Create Table'];
		$columns = array();
		$primaryKeys = array();
		$constraints = array();
		
		// parse columns
		if (preg_match_all(self::CREATE_TABLE_SQL_COLUMNS_REGEX, $createTableSql, $columns, PREG_SET_ORDER)) {
			$table = new \jamend\Selective\Table($name, $this);
			
			foreach ($columns as $columnInfo) {
				$column = new \jamend\Selective\Column($table);
				$column->name = $columnInfo['name'];
				$column->type = $columnInfo['type'];
				$column->default = isset($columnInfo['default']) ? $columnInfo['default'] : null;
				$column->allowNull = $columnInfo['allowNull'] === 'NULL';
				
				if ($column->type == 'set' || $column->type == 'enum') {
					$options = explode(',', $column->length);
					$i = 0;
					foreach ($options as $option) {
						$column->options[($column->type == 'set' ? pow(2, $i) : $i)] = trim($option, '`');
						$i++;
					}
				} else {
					$column->length = $columnInfo['length'] === '' ? null : $columnInfo['length'];
				}
				
				$table->columns[$column->name] = $column;
			}
			
			// parse primary keys
			preg_match(self::CREATE_TABLE_SQL_PRIMARY_KEY_REGEX, $createTableSql, $primaryKeys);
			unset($primaryKeys[0]);
			
			foreach ($primaryKeys as $primaryKey) {
				$primaryKey = trim($primaryKey, '`');
				$table->keys[] = $primaryKey;
				$table->columns[$primaryKey]->isPrimaryKey = true;
			}
			
			// parse relationships
			preg_match_all(self::CREATE_TABLE_SQL_CONSTRAINT_REGEX, $createTableSql, $constraints, PREG_SET_ORDER);
			foreach ($constraints as $constraint) {
				$table->relatedTables[$constraint['relatedTable']] = array(
					'localColumns' => explode('`, `', trim($constraint['localColumns'], '`')),
					'relatedColumns' => explode('`, `', trim($constraint['relatedColumns'], '`')),
				);
				$table->constraints[$constraint['name']] = array(
					'localColumns' => explode('`, `', trim($constraint['localColumns'], '`')),
					'relatedTable' => $constraint['relatedTable'],
					'relatedColumns' => explode('`, `', trim($constraint['relatedColumns'], '`')),
				);
			}
			
			return $table;
		} else {
			throw new \Exception('Could not parse table definition');
		}
	}
}