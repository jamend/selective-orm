<?php
namespace jamend\Selective\ClassMapper;

use \jamend\Selective\ClassMapper;
use \jamend\Selective\Table;

/**
 * Maps table names to table/record classes using provided callbacks
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Callback implements ClassMapper
{
    private $tableClassCallback;
    private $recordClassCallback;

    /**
     * Load class mapper parameters
     * @param array $parameters
     * - table - table class callback
     * - record - record class callback
     */
    public function loadParameters($parameters)
    {
        $this->tableClassCallback = $parameters['table'];
        $this->recordClassCallback = $parameters['record'];
    }

    /**
     * Get the class for a table by name
     * @param string $tableName
     * @return string
     */
    public function getClassForTable($tableName)
    {
        $callback = $this->tableClassCallback;
        return $callback($tableName);
    }

    /**
     * Get a class for a record by its table's name
     * @param Table $table
     * @param string $id
     * @return string
     */
    public function getClassForRecord($tableName)
    {
        $callback = $this->recordClassCallback;
        return $callback($tableName);
    }
}