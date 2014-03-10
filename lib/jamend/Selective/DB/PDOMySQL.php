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
	 * Set the connection database name
	 * @param string $dbname
	 */
	public function setDbname($dbname) {
		$this->dbname = $dbname;
	}

	/**
	 * Set the connection host name
	 * @param string $host
	 */
	public function setHost($host) {
		$this->host = $host;
	}

	/**
	 * Set the connection username
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}
	
	/**
	 * Set the connection password
	 * @param string $password
	 */
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
		return "`{$this->getPrefix()}{$table->getName()}`";
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
	 * Get the MySQL-specific representation of a value for a column
	 * @param Column $column
	 * @return string
	 */
	public function getColumnSQLExpression(\jamend\Selective\Column $column) {
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
	public function getColumnDenormalizedValue(\jamend\Selective\Column $column, $value) {
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
	
	/**
	 * Get a list of names of the table in the database
	 * @return array
	 */
	public function getTables() {
		// Cache the list of tables
		if (!isset($this->tables)) {
			$this->tables = array();
			$tables = $this->fetchAll("SHOW TABLES FROM `{$this->dbname}` LIKE ?", array("{$this->getPrefix()}%"));
			$offset = strlen($this->getPrefix());
			foreach ($tables as $row) {
				$this->tables[] = substr(current($row), $offset);
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
		$createTableInfo = $this->fetchAll("SHOW CREATE TABLE `{$this->getPrefix()}{$name}`");
		$createTableSql = $createTableInfo[0]['Create Table'];
		$columns = array();
		$primaryKeys = array();
		$constraints = array();
		
		// parse columns
		if (preg_match_all(self::CREATE_TABLE_SQL_COLUMNS_REGEX, $createTableSql, $columns, PREG_SET_ORDER)) {
			$table = new \jamend\Selective\Table($name, $this);
			
			foreach ($columns as $columnInfo) {
				$column = new \jamend\Selective\Column($table);
				$column
					->setName($columnInfo['name'])
					->setType($columnInfo['type'])
					->setDefault(isset($columnInfo['default']) ? $columnInfo['default'] : null)
					->setAllowNull(!isset($columnInfo['allowNull']) || $columnInfo['allowNull'] === 'NULL')
				;
				
				if ($column->getType() == 'set' || $column->getType() == 'enum') {
					$rawOptions = explode("','", trim($columnInfo['length'], "'"));
					$options = array();
					$i = 0;
					foreach ($rawOptions as $option) {
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
			$offset = strlen($this->getPrefix());
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