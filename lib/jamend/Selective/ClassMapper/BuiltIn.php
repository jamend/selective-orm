<?php
namespace jamend\Selective\ClassMapper;

use \jamend\Selective\ClassMapper;

/**
 * Maps table names to built-in Table Record classes
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class BuiltIn implements ClassMapper
{
    /**
     * Load class mapper parameters
     * @param array $parameters
     */
    public function loadParameters($parameters)
    {
    }

    /**
     * Get the class for a table by name
     * @param string $tableName
     * @return string
     */
    public function getClassForTable($tableName)
    {
        return 'jamend\Selective\Table';
    }

    /**
     * Get a class for a record by its table's name
     * @param Table $table
     * @param string $id
     * @return string
     */
    public function getClassForRecord($tableName)
    {
        return 'jamend\Selective\Record';
    }
}