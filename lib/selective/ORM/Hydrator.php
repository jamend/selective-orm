<?php
namespace selective\ORM;

/**
 * Builds records for a given query
 * @author Jonathan Amend <j.amend@gmail.com>
 * @copyright 2014, Jonathan Amend
 */
interface Hydrator
{
    /**
     * Get the next record from the result
     * @param string &$id will be set to the record's ID
     * @return bool|array|Record
     */
    public function getRecord(&$id = null);
}