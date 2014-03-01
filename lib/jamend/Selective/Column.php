<?php
namespace jamend\Selective;

/**
 * Represents a table column
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Column {
	public $info;
	public $table;
	public $name;
	public $default = null;
	public $isPrimaryKey;
	public $allowNull;
	public $type;
	public $length;
	public $options = array();
	
	/**
	 * FIXME MySQL-specific; move to DB implementation
	 * @param array $info row from information_schema.COLUMNS
	 * @param \jamend\ORM\Table $table
	 */
	public function __construct($info, $table) {
		$this->info = $info;
		$this->table = $table;
		$this->name = $info['COLUMN_NAME'];
		
		if ($info['COLUMN_DEFAULT'] !== '') {
			$this->default = $info['COLUMN_DEFAULT'];
		}

		$this->isPrimaryKey = $info['COLUMN_KEY'] === 'PRI';
		$this->allowNull = $info['IS_NULLABLE'] !== 'NO';
		
		// parse the column type, ex. varchar(32) or enum('opt1','opt2')
		if (preg_match('/(([a-z]+)(\(([^\)]+)\)))?/', $info['COLUMN_TYPE'], $matches)) {
			if (empty($matches[2])) {
				$this->type = $info['COLUMN_TYPE'];
			} else {
				$this->type = $matches[2];
			}
			if (!empty($matches[4])) {
				if ($this->type == 'set' || $this->type == 'enum') {
					$quotedOptions = explode(',', $matches[4]);
					$i = 0;
					foreach ($quotedOptions as $quotedOption) {
						$this->options[($this->type == 'set' ? pow(2, $i) : $i)] = substr($quotedOption, 1, -1);
						$i++;
					}
				} else {
					$this->length = $matches[4];
				}
			}
		}
	}
	
	/**
	 * Get the table of this column
	 * @return Table
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * Get the column name
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Get the full quoted identifier including database/table name
	 * @return string
	 */
	public function getFullIdentifier() {
		return $this->getTable()->getDB()->getColumnFullIdentifier($this);
	}
	
	/**
	 * Get the quoted identifier for the column name
	 * @return string
	 */
	public function getBaseIdentifier() {
		return $this->getTable()->getDB()->getColumnBaseIdentifier($this);
	}
}