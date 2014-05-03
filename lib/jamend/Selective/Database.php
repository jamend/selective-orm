<?php
namespace jamend\Selective;

/**
 * Represents a database
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Database
{
    /**
     * @var string
     */
    private $name = '';
    /**
     * @var Driver
     */
    private $driver;
    /**
     * @var ClassMapper
     */
    private $classMapper;
    /**
     * @var string
     */
    private $prefix = '';

    /**
     *
     * @param string $name database name
     * @param string $driver Driver implementation class name
     * @param array $parameters Driver-specific parameters
     * @param array $classMapperConfig Class mapper configuration (optional)
     */
    public function __construct($name, $driver, $parameters, $classMapper = array())
    {
        $this->name = $name;

        if (isset($parameters['prefix'])) {
            $this->prefix = $parameters['prefix'];
            unset($parameters['prefix']);
        }

        // load driver
        if ($driver{0} !== '\\') {
            // class is relative to this namespace
            $driver = "\jamend\Selective\Driver\\{$driver}";
        }

        $this->driver = new $driver();
        $this->driver->loadParameters($parameters);
        $this->driver->connect($this);

        // load class mapper
        if (isset($classMapper['class'])) {
            $classMapperClass = $classMapper['class'];
            unset($classMapper['class']);
            if ($classMapperClass{0} !== '\\') {
                // class is relative to this namespace
                $classMapperClass = "\jamend\Selective\ClassMapper\\{$classMapperClass}";
            }
        } else {
            $classMapperClass = "\jamend\Selective\ClassMapper\BuiltIn";
        }

        $this->classMapper = new $classMapperClass();
        $this->classMapper->loadParameters($classMapper);
    }

    /**
     * Get the database name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the driver
     * @return Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the class mapper
     * @return ClassMapper
     */
    public function getClassMapper()
    {
        return $this->classMapper;
    }

    /**
     * Get the database table prefix
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the database table prefix
     * @param $prefix string
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Checks if a table exists
     * @param string $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        return in_array($tableName, $this->getTables($this));
    }

    /**
     * Get a list of names of the table in the database
     * @return string[]
     */
    public function getTables()
    {
        return $this->getDriver()->getTables($this);
    }

    /**
     * Get a Table by name
     * @param string $name
     * @return Table
     */
    public function getTable($name)
    {
        return $this->getDriver()->getTable($this, $name);
    }

    /**
     * Get a Table object for the given name
     * @param String $name
     * @return \jamend\Selective\Table
     */
    public function __get($name)
    {
        // Cache the table
        return $this->getTable($name);
    }
}