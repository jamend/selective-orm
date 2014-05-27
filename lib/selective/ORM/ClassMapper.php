<?php
namespace selective\ORM;

/**
 * Maps table names to table/record classes
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
interface ClassMapper
{
    // table class
    // record class

    // columns to property
    // mutiple records per result of join

    /**
     * Load mapper parameters
     * @param array $parameters
     */
    public function loadParameters($parameters);

    /**
     *
     * @param string $tableName
     * @return string
     */
    public function getClassForTable($tableName);

    /**
     *
     * @param string $tableName
     * @return string
     */
    public function getClassForRecord($tableName);
}