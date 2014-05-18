<?php
namespace jamend\Selective\RecordSet;

use jamend\Selective\RecordSet;
use jamend\Selective\Record;

/**
 * Represents the records in a table in the database that can be used like an
 * array where the keys map to record IDs
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Buffered extends RecordSet implements \ArrayAccess, \Countable
{
    /**
     * @var Record[]
     */
    protected $records = [];

    /**
     * Load the records for this record set
     */
    private function load()
    {
        if ($this->isDirty()) {
            $hydrator = $this->getDriver()->getHydrator($this->getTable(), $this->query);
            $this->records = [];
            while (($record = $hydrator->getRecord($id)) !== false) {
                $this->records[$id] = $record;
            }
            $this->dirty = false;
        }
    }

    /**
     * Get the first record from this record set
     * @return Record
     */
    public function first()
    {
        $this->load();
        reset($this->records);
        return current($this->records);
    }

    /**
     * Get the first record from this record set
     * @return Record
     */
    public function last()
    {
        $this->load();
        reset($this->records);
        return end($this->records);
    }

    /**
     * Return this result set's data as an array of arrays
     * @return array[]
     */
    public function toArray()
    {
        $records = [];
        $hydrator = $this->getDriver()->getHydrator($this->getTable(), $this->query, true);
        while (($record = $hydrator->getRecord($id)) !== false) {
            $records[$id] = $record;
        }
        return $records;
    }

    // Array iteration/traversal

    /**
     * Get the count of records
     * @return int
     */
    public function count()
    {
        $this->load();
        return count($this->records);
    }

    /**
     * Check if a record exists by its ID
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->load();
        return isset($this->records[$offset]);
    }

    /**
     * Get a record by its ID
     * @param mixed $offset
     * @return int
     */
    public function offsetGet($offset)
    {
        $this->load();
        return isset($this->records[$offset]) ? $this->records[$offset] : null;
    }

    /**
     * Get a record by ID
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->dirty = false;
        $this->records[$offset] = $value;
    }

    /**
     * Remove a record by ID
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->records[$offset]);
    }

    /**
     * Get the current item
     * @return Record
     */
    public function current()
    {
        return current($this->records);
    }

    /**
     * Set the current item to the next item
     */
    public function next()
    {
        next($this->records);
    }

    /**
     * Get the current position
     * @return string
     */
    public function key()
    {
        return key($this->records);
    }

    /**
     * Called after rewind() or next() when a foreach advances; must return
     * true or false to indicate that there is an item available at the new
     * position
     * @return bool
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    /**
     * Called when a foreach is started
     */
    public function rewind()
    {
        $this->load();
        reset($this->records);
    }
}