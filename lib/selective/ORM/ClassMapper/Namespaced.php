<?php
namespace selective\ORM\ClassMapper;

use \selective\ORM\Table;
use \selective\ORM\Record;

/**
 * Maps table names to table and record classes prefixed by a given namespace
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Namespaced extends BuiltIn
{
    private $namespace;
    private $fallback = true;

    /**
     * Load class mapper parameters
     * @param array $parameters
     * - namespace - namespace to prefix classes with
     * - fallback - whether to fall back to BuiltIn mapper if a namespaced class is not found
     */
    public function loadParameters($parameters)
    {
        $this->namespace = $parameters['namespace'];
        if (isset($parameters['fallback'])) $this->fallback = $parameters['fallback'];
    }

    /**
     * Get the class for a table by name
     * @param string $tableName
     * @return string
     */
    public function getClassForTable($tableName)
    {
        $class = $this->namespace . '\\' . $tableName . 'Table';
        if (class_exists($class)) {
            return $class;
        } else if ($this->fallback) {
            return parent::getClassForTable($tableName);
        } else {
            throw new \Exception("Missing class '{$class}' for table '{$tableName}'");
        }
    }

    /**
     * Get a class for a record by its table's name
     * @param Table $table
     * @param string $id
     * @return string
     */
    public function getClassForRecord($tableName)
    {
        $class = $this->namespace . '\\' . $tableName;
        if (class_exists($class)) {
            return $class;
        } else if ($this->fallback) {
            return parent::getClassForRecord($tableName);
        } else {
            throw new \Exception("Missing class '{$class}' for table '{$tableName}'");
        }
    }
}