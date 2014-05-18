<?php
namespace jamend\Selective;

/**
 * Represents the records of a table in the database
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
abstract class RecordSet implements \Iterator
{
    /**
     * @var Table
     */
    protected $table;
    /**
     * @var Query
     */
    protected $query;
    /**
     * @var Driver
     */
    protected $driver;
    /**
     * @var bool
     */
    protected $dirty = true;

    /**
     * Make a record set for the given table
     * @param Table $table
     */
    protected function __construct(Table $table)
    {
        $this->table = $table;
        $this->query = new Query();
        $this->driver = $table->getDriver();
    }

    /**
     * Get the table of this
     * @return \jamend\Selective\Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the query object
     * @return \jamend\Selective\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the driver
     * @return \jamend\Selective\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get a clone of this record set, ready for more filters/criteria
     * @return RecordSet
     */
    public function openRecordSet()
    {
        $class = get_class($this);
        $recordSet = new $class($this->getTable());
        $recordSet->query = clone $this->query;
        return $recordSet;
    }

    /**
     * Get an unbuffered RecordSet
     * @return RecordSet\Unbuffered
     */
    public function unbuffered()
    {
        $recordSet = new RecordSet\Unbuffered($this->getTable());
        $recordSet->query = clone $this->query;
        return $recordSet;
    }

    /**
     * Return a new record set filtered by the given where clause
     * @param string $criteria where clause
     * @param mixed... $params
     * @return RecordSet
     */
    public function where($criteria)
    {
        $params = func_get_args();
        $criteria = array_shift($params);
        $recordSet = $this->openRecordSet();
        $recordSet->query->addWhere($criteria, $params);
        return $recordSet;
    }

    /**
     * Return a new record set filtered by the given having clause
     * @param string $criteria where clause
     * @param mixed... $params
     * @return RecordSet
     */
    public function having($criteria)
    {
        $params = func_get_args();
        $criteria = array_shift($params);
        $recordSet = $this->openRecordSet();
        $recordSet->query->addHaving($criteria, $params);
        return $recordSet;
    }

    /**
     * Return a new record set with the given limit clause
     * @param int $limit
     * @param int $offset
     * @return RecordSet
     */
    public function limit($limit, $offset = 0)
    {
        $recordSet = $this->openRecordSet();
        $recordSet->query->setLimit($limit, $offset);
        return $recordSet;
    }

    /**
     * Return a new record set order by the given field and direction
     * @param string $field
     * @param string $direction ASC or DESC
     * @return RecordSet
     */
    public function orderBy($field, $direction = 'ASC')
    {
        $recordSet = $this->openRecordSet();
        $recordSet->query->addOrderBy($field, $direction);
        return $recordSet;
    }

    /**
     * Pre-load related table with result set
     * @param string $tableName
     * @throws \Exception
     * @return RecordSet
     */
    public function with($tableName)
    {
        $on = [];
        $relatedTable = $this->getTable()->getDatabase()->getTable($tableName);
        $columns = array_keys($relatedTable->getColumns());
        $cardinality = null;
        $joinType = 'left';

        if (isset($relatedTable->relatedTables[$this->getTable()->getName()])) {
            $constraintName = $relatedTable->relatedTables[$this->getTable()->getName()];
            $constraint = $relatedTable->constraints[$constraintName];
            $cardinality = Query::CARDINALITY_ONE_TO_MANY;

            for ($i = 0; $i < count($constraint['localColumns']); $i++) {
                $on[$constraint['relatedColumns'][$i]] = $constraint['localColumns'][$i];
            }
        } else if (isset($this->getTable()->relatedTables[$relatedTable->getName()])) {
            $constraintName = $this->getTable()->relatedTables[$relatedTable->getName()];
            $constraint = $this->getTable()->constraints[$constraintName];
            $cardinality = Query::CARDINALITY_MANY_TO_ONE;

            for ($i = 0; $i < count($constraint['localColumns']); $i++) {
                $localColumn = $this->getTable()->getColumn($constraint['localColumns'][$i]);
                if (!$localColumn->isAllowNull()) {
                    $joinType = 'inner';
                }
                $on[$constraint['localColumns'][$i]] = $constraint['relatedColumns'][$i];
            }
        } else {
            throw new \Exception("Table {$this->getTable()->getName()} is not related to {$relatedTable->getName()}");
        }

        $recordSet = $this->openRecordSet();
        $recordSet->query->addJoin($joinType, $tableName, $on, null, $columns, $cardinality);
        return $recordSet;
    }

    /**
     * Return a new result set using the given raw SQL
     * @param string $sql
     * @return RecordSet
     */
    public function sql($sql)
    {
        $recordSet = $this->openRecordSet();
        $recordSet->query->setRawSql($sql);
        return $recordSet;
    }

    /**
     * Tracks if the records have been loaded after a change in the query/criteria
     * @return boolean
     */
    public function isDirty()
    {
        return $this->dirty;
    }
}