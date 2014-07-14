<?php
namespace selective\ORM;

/**
 * Builds records for a given query
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Hydrator
{
    private $table;
    private $asArray = false;
    private $rawSql = false;

    private $fetchStyle;
    private $tableName;
    /** @var Table[] */
    private $joinedTables = [];
    private $columnOrdinalMap = [];
    private $properties = [];
    /** @var int[] */
    private $cardinalities = [];

    /** @var \PDOStatement */
    private $result;
    /** @var array */
    private $currentRow;
    private $currentId;
    private $rawSqlId = 0;

    /**
     * @param Driver $driver
     * @param Table $table
     * @param Query $query
     * @param bool $asArray
     */
    public function __construct(Driver $driver, Table $table, Query $query, $asArray = false)
    {
        $this->asArray = $asArray;
        $this->table = $table;

        $params = [];

        $this->tableName = $table->getName();
        if (!$this->asArray) $this->recordClasses = [$this->tableName => $table->getDatabase()->getClassMapper()->getClassForRecord($this->tableName)];

        $sql = $query->getRawSql();
        if ($sql === null) {
            $sql = $driver->buildSQL($table, $query, $params);
            $this->fetchStyle = \PDO::FETCH_NUM;

            foreach ($table->getColumns() as $column) {
                if ($column->isPrimaryKey()) {
                    $this->primaryKeyOrdinals[$this->tableName][$column->getOrdinal()] = true;
                }
                $this->columnOrdinalMap[$this->tableName][$column->getOrdinal()] = $column->getName();
            }

            $offset = count($table->getColumns());
            foreach ($query->getJoins() as $join) {
                if (isset($join['cardinality'])) {
                    $joinedTableName = $join['table'];
                    $joinedTable = $table->getDatabase()->getTable($joinedTableName);
                    $this->joinedTables[$joinedTableName] = $joinedTable;
                    if (!$this->asArray) $this->recordClasses[$joinedTableName] = $joinedTable->getDatabase()->getClassMapper()->getClassForRecord($joinedTableName);

                    foreach ($joinedTable->getColumns() as $column) {
                        if ($column->isPrimaryKey()) {
                            $this->primaryKeyOrdinals[$joinedTableName][$offset + $column->getOrdinal()] = true;
                        }
                        $this->columnOrdinalMap[$joinedTableName][$offset + $column->getOrdinal()] = $column->getName();
                    }

                    $this->cardinalities[$joinedTableName] = $join['cardinality'];
                    if ($join['cardinality'] === Query::CARDINALITY_ONE_TO_MANY) {
                        $this->properties[$joinedTableName] = $joinedTableName;
                    } else {
                        $this->properties[$joinedTableName] = array_keys($join['on']);
                    }

                    $offset += count($joinedTable->getColumns());
                }
            }
        } else {
            $this->fetchStyle = \PDO::FETCH_ASSOC;
            $this->rawSql = true;
        }

        $this->result = $driver->query($sql, $params);
        $this->fetchNextRow();
    }

    /**
     * Load the next row from the result
     */
    private function fetchNextRow()
    {
        $this->currentRow = $this->result->fetch($this->fetchStyle);
    }

    /**
     * Get the next record from the result
     * @param string &$id will be set to the record's ID
     * @return bool|array|Record
     */
    public function getRecord(&$id = null)
    {
        if (!$this->currentRow) {
            return false;
        }

        if ($this->rawSql) {
            $id = $this->rawSqlId;
            $this->rawSqlId++;
            if ($this->asArray) {
                $row = $this->currentRow;
                $this->fetchNextRow();
                return $row;
            } else {
                $recordClass = $this->recordClasses[$this->tableName];
                $record = new $recordClass($this->table, true, $this->currentRow);
                $this->fetchNextRow();
                return $record;
            }
        } else {
            $this->currentId = implode(',', array_intersect_key($this->currentRow, $this->primaryKeyOrdinals[$this->tableName]));
            $data = array_combine($this->columnOrdinalMap[$this->tableName], array_intersect_key($this->currentRow, $this->columnOrdinalMap[$this->tableName]));
            if ($this->asArray) {
                $record = $data;
            } else {
                $recordClass = $this->recordClasses[$this->tableName];
                $record = new $recordClass($this->table, true, $data);
            }

            $relatedRecords = [];
            $oneToManyRecordSets = [];

            $this->loadRelatedRecords($record, $oneToManyRecordSets, $relatedRecords);

            $haveNextRow = false;
            while ($this->currentRow = $this->result->fetch($this->fetchStyle)) {
                $rowId = implode(',', array_intersect_key($this->currentRow, $this->primaryKeyOrdinals[$this->tableName]));
                if ($rowId !== $this->currentId) {
                    $haveNextRow = true;
                    break;
                }

                $this->loadRelatedRecords($record, $recordSets, $relatedRecords);
            }

            if (!$haveNextRow) $this->fetchNextRow();
            $id = $this->currentId;
            return $record;
        }
    }

    /**
     * Load related records for table names that were specified in RecordSet::with() calls
     * @param array|Record $record
     * @param &array $oneToManyRecordSets
     * @param &array $relatedRecords
     */
    private function loadRelatedRecords(&$record, &$oneToManyRecordSets, &$relatedRecords)
    {
        foreach ($this->joinedTables as $joinedTableName => $joinedTable) {
            if (isset($this->currentRow[key($this->primaryKeyOrdinals[$joinedTableName])])) {
                $joinedId = implode(',', array_intersect_key($this->currentRow, $this->primaryKeyOrdinals[$joinedTableName]));

                if (!isset($relatedRecords[$joinedTableName][$joinedId])) {
                    $data = array_combine($this->columnOrdinalMap[$joinedTableName], array_intersect_key($this->currentRow, $this->columnOrdinalMap[$joinedTableName]));
                    if ($this->asArray) {
                        $relatedRecords[$joinedTableName][$joinedId] = $data;
                    } else {
                        $recordClass = $this->recordClasses[$joinedTableName];
                        $relatedRecords[$joinedTableName][$joinedId] = new $recordClass($joinedTable, true, $data);
                    }
                }

                if ($this->cardinalities[$joinedTableName] === Query::CARDINALITY_ONE_TO_MANY) {
                    $property = $this->properties[$joinedTableName];
                    if ($this->asArray) {
                        $record[$property][$joinedId] = $relatedRecords[$joinedTableName][$joinedId];
                    } else {
                        if (!isset($oneToManyRecordSets[$joinedTableName])) {
                            $recordSet = $joinedTable->openRecordSet();
                            $recordSet->flagClean();
                            $oneToManyRecordSets[$joinedTableName] = $recordSet;
                            $record->{$property} = $recordSet;
                        }
                        $oneToManyRecordSets[$joinedTableName][$joinedId] = $relatedRecords[$joinedTableName][$joinedId];
                    }
                } else {
                    foreach ($this->properties[$joinedTableName] as $property) {
                        if ($this->asArray) {
                            $record[$property] = $relatedRecords[$joinedTableName][$joinedId];
                        } else {
                            $record->{$property} = $relatedRecords[$joinedTableName][$joinedId];
                        }
                    }
                }
            }
        }
    }
}