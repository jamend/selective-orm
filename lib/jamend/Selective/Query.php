<?php
namespace jamend\Selective;

/**
 * Represents an SQL query
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Query
{
    private $where = array();
    private $having = array();
    private $limit = null;
    private $orderBy = array();

    /**
     * Add a where condition
     * @param string $criteria
     * @param array $params
     */
    public function addWhere($criteria, $params)
    {
        $this->where[] = array($criteria, $params);
    }

    /**
     * Get the where conditions
     * @param string $criteria
     * @param array $params
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Add a having condition
     * @return array[]
     */
    public function addHaving($criteria, $params)
    {
        $this->having[] = array($criteria, $params);
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
        $this->limit = array($limit, $offset);
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
        $this->orderBy[] = array($field, $direction);
    }

    /**
     * Get the order by fields and directions
     * @return array[]
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }
}