<?php
namespace selective\ORM;

/**
 * Represents a record in a table
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Record
{
    // Keep internal state in _meta so that any instances look more like plain old objects
    private $_meta = [
    	'foreignRecords' => []
    ];

    /**
     * Get a record in the given table.
     * This class is usually instantiated by Driver::getRecords(), which
     * sets column values as properties.
     * @param Table $table
     * @param bool $exists Is this a real record, or a new one that we will probably insert later?
     * @param array $data optional array of properties => values
     */
    public function __construct(Table $table, $exists = true, $data = null)
    {
        $this->_meta['table'] = $table;
        $this->_meta['exists'] = $exists;
        $this->_meta['existed'] = $exists;
        $this->_meta['driver'] = $table->getDriver();
        if (isset($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
        foreach ($table->getForeignKeys() as $localColumn => $foreignKey) {
            if (property_exists($this, $localColumn)) {
                $this->_meta['foreignRecords'][$localColumn] = $this->{$localColumn};
                // unset the property, so that the magic __getter will be invoked
                unset($this->{$localColumn});
            } else {
                $this->_meta['foreignRecords'][$localColumn] = null;
            }
        }
    }

    /**
     * Get this record's table
     * @return Table
     */
    public function getTable()
    {
        return $this->_meta['table'];
    }

    /**
     * Returns true if this record exists in the table
     * @return boolean
     */
    public function exists()
    {
        return $this->_meta['exists'];
    }

    /**
     * Returns true if this record ever existed, i.e. if it was deleted
     * @return boolean
     */
    public function existed()
    {
        return $this->_meta['existed'];
    }

    /**
     * Get the driver
     * @return \selective\ORM\Driver
     */
    public function getDriver()
    {
        return $this->_meta['driver'];
    }

    /**
     * Get this record's column data as an associative array
     * @return array
     */
    public function toArray()
    {
        $data = get_object_vars($this);
        unset($data['_meta']);
        return array_merge($data, $this->_meta['foreignRecords']);
    }

    /**
     * Get the ID of this record; for a multiple-primary key table, the PK
     * values are joined by commas
     * @return string
     */
    public function getId()
    {
        $id = '';
        foreach ($this->getTable()->getPrimaryKeys() as $columnName) {
            if (isset($this->{$columnName})) {
                $id .= ',' . $this->{$columnName};
            }
        }
        if (strlen($id) > 1) {
            return substr($id, 1);
        } else {
            return null;
        }
    }

    /**
     * Get a table related to this record by table name
     * @param string $tableName
     * @return \selective\ORM\Table|boolean
     */
    public function getRelatedTable($tableName)
    {
        if ($this->getTable()->getDatabase()->hasTable($tableName)) {
            $relatedTable = $this->getTable()->getDatabase()->getTable($tableName);
            if (isset($relatedTable->relatedTables[$this->getTable()->getName()])) {
                $constraintName = $relatedTable->relatedTables[$this->getTable()->getName()];
                $constraint = $relatedTable->constraints[$constraintName];

                for ($i = 0; $i < count($constraint['localColumns']); $i++) {
                    $localColumn = $relatedTable->getColumn($constraint['localColumns'][$i]);
                    $foreignColumnName = $constraint['relatedColumns'][$i];
                    $relatedTable = $relatedTable->where($localColumn->getFullIdentifier() . ' = ?', $this->{$foreignColumnName});
                }

                return $relatedTable;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get the related record by value of the given column name
     * @param string $columnName
     * @return \selective\ORM\Record|boolean
     */
    public function getForeignRecord($columnName)
    {
        if (isset($this->_meta['foreignRecords'][$columnName])) {
            $constraintName = $this->getTable()->getForeignKeys()[$columnName];
            $constraint = $this->getTable()->constraints[$constraintName];
            $relatedTable = $this->getTable()->getDatabase()->getTable($constraint['relatedTable']);
            $recordSet = $relatedTable;

            for ($i = 0; $i < count($constraint['localColumns']); $i++) {
                $relatedColumn = $relatedTable->getTable()->getColumn($constraint['relatedColumns'][$i]);
                $recordSet = $recordSet->where($relatedColumn->getFullIdentifier() . ' = ?', $this->_meta['foreignRecords'][$constraint['localColumns'][$i]]);
            }

            return $recordSet->first();
        } else {
            return false;
        }
    }

    /**
     * Get a table related to this record by table name
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (($table = $this->getRelatedTable($name)) !== false) {
            $this->$name = $table;
            return $table;
        } else if (($foreignRecord = $this->getForeignRecord($name)) !== false) {
            $this->$name = $foreignRecord;
            return $foreignRecord;
        } else {
            trigger_error('Undefined property: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Get the raw value for a property
     * @param string $name
     * @return mixed
     */
    public function getRawPropertyValue($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        } else if (array_key_exists($name, $this->_meta['foreignRecords'])) {
            return $this->_meta['foreignRecords'][$name];
        } else {
            trigger_error('Undefined property: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Checks if a table related to this record exists by table name
     * @param string $tableName
     * @return \selective\ORM\Table|boolean
     */
    public function hasRelatedTable($tableName)
    {
        if ($this->getTable()->getDatabase()->hasTable($tableName)) {
            $relatedTable = $this->getTable()->getDatabase()->getTable($tableName);
            return isset($relatedTable->relatedTables[$this->getTable()->getName()]);
        } else {
            return false;
        }
    }

    /**
     * Checks if there is a related record by value of the given column name
     * @param string $columnName
     * @return boolean
     */
    public function hasForeignRecord($columnName)
    {
        return isset($this->_meta['foreignRecords'][$columnName]);
    }

    /**
     * Checks if a table related to this record exists by table name
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->hasRelatedTable($name) || $this->hasForeignRecord($name);
    }

    /**
     * Saves this record in the table; This will result in an INSERT or UPDATE
     * query based of if this record already exists
     * @return boolean True if a change was made to the database
     */
    public function save()
    {
        if ($this->exists()) {
            $affectedRows = $this->getDriver()->updateRecord($this);
        } else {
            $affectedRows = $this->getDriver()->insertRecord($this);
            $this->_meta['exists'] = $affectedRows === 1;
        }

        $this->getTable()->flagDirty();
        return $affectedRows === 1;
    }

    /**
     * Delete this record from the database
     * @return boolean True if the record is deleted
     */
    public function delete()
    {
        $affectedRows = $this->getDriver()->deleteRecord($this);
        $this->_meta['exists'] = $affectedRows === false && $this->_meta['exists'];
        $this->getTable()->flagDirty();
        return !$this->_meta['exists'];
    }

    /**
     * Use the primary key values as this record's string representation
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getID();
    }
}