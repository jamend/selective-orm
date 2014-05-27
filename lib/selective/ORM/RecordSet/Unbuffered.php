<?php
namespace selective\ORM\RecordSet;

use selective\ORM\RecordSet;
use selective\ORM\Record;
use selective\ORM\Hydrator;

/**
 * Represents the records in a table in the database that can be iterated over
 * like a stream
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
class Unbuffered extends RecordSet
{
    /** @var Record */
    private $current;
    /** @var string */
    private $id;

    /**
     * @var Hydrator
     */
    protected $hydrator = [];

    /**
     * Load the records for this record set
     */
    private function load()
    {
        $this->hydrator = $this->getDriver()->getHydrator($this->getTable(), $this->query);
    }

    /**
     * Get the current item
     * @return Record
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Set the current item to the next item
     */
    public function next()
    {
        $this->current = $this->hydrator->getRecord($this->id);
    }

    /**
     * Get the current position
     * @return string
     */
    public function key()
    {
        return $this->id;
    }

    /**
     * Called after rewind() or next() when a foreach advances; must return
     * true or false to indicate that there is an item available at the new
     * position
     * @return bool
     */
    public function valid()
    {
        return $this->current !== false;
    }

    /**
     * Called when a foreach is started
     */
    public function rewind()
    {
        $this->load();
        $this->current = $this->hydrator->getRecord($this->id);
    }
}