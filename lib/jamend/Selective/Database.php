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
     * @var string
     */
    private $prefix = '';

    /**
     *
     * @param string $name database name
     * @param string $driver Driver implementation class name
     * @param array $parameters Driver-specific parameters
     */
    public function __construct($name, $driver, $parameters)
    {
        $this->name = $name;

        // load driver
        if ($driver{0} === '\\') {
            // driver class has absolute namespace
            $driverClass = $driver;
        } else {
            // driver class is relative to this namespace
            $driverClass = "\jamend\Selective\Driver\\{$driver}";
        }
        $this->driver = new $driverClass();
        if (isset($parameters['prefix'])) {
            $this->prefix = $parameters['prefix'];
            unset($parameters['prefix']);
        }
        $this->driver->loadParameters($parameters);
        $this->driver->connect($this);
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
        return $this->prefix = $prefix;
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
        return $this->$name = $this->getTable($name);
    }
}