<?php
namespace jamend\Selective;

/**
 * Represents an SQL query
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Query
{
    const CARDINALITY_ONE_TO_MANY = 0;
    const CARDINALITY_MANY_TO_ONE = 1;

    private $rawSql = null;
    private $where = [];
    private $having = [];
    private $limit = null;
    private $orderBy = [];
    private $joins = [];

    /**
     * Add a where condition
     * @param string $criteria
     * @param array $params
     */
    public function addWhere($criteria, $params)
    {
        $this->where[] = [$criteria, $params];
    }

    /**
     * Get the where conditions
     * @return array[]
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Add a having condition
     * @param string $criteria
     * @param array $params
     */
    public function addHaving($criteria, $params)
    {
        $this->having[] = [$criteria, $params];
    }

    /**
     * Get the having conditions
     * @return array[]
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Set the limit clause
     * @param int $limit
     * @param int $offset
     */
    public function setLimit($limit, $offset = 0)
    {
        $this->limit = [$limit, $offset];
    }

    /**
     * Get the limit clause
     * @return array[]
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Add order by field and direction
     * @param string $field
     * @param string $direction ASC or DESC
     */
    public function addOrderBy($field, $direction = 'ASC')
    {
        $this->orderBy[] = [$field, $direction];
    }

    /**
     * Get the order by fields and directions
     * @return array[]
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Add a join
     * @param string $type
     * @param string $table
     * @param array $on column mapping for ON clause
     * @param string $alias optional alias for joined table
     * @param array $columns optional list of columns to include from joined table
     * @param int $columns optional one of the CARDINALITY_ consts
     */
    public function addJoin($type, $table, $on, $alias = null, $columns = null, $cardinality = null)
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'on' => $on,
            'alias' => $alias,
            'columns' => $columns,
            'cardinality' => $cardinality
        ];
    }

    /**
     * Get the joins
     * @return array[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Set raw SQL for this query
     * @param string $rawSql
     */
    public function setRawSql($rawSql)
    {
        $this->rawSql = $rawSql;
    }

    /**
     * Gt raw SQL for this query
     * @return string
     */
    public function getRawSql()
    {
        return $this->rawSql;
    }
}