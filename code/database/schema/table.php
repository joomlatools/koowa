<?php
/**
 * Kodekit - http://timble.net/kodekit
 *
 * @copyright   Copyright (C) 2007 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     MPL v2.0 <https://www.mozilla.org/en-US/MPL/2.0>
 * @link        https://github.com/timble/kodekit for the canonical source repository
 */

namespace Kodekit\Library;

/**
 * Table Database Schema
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Database\Schema
 */
class DatabaseSchemaTable
{
    /**
     * Table name
     *
     * @var string
     */
    public $name;

    /**
     * The storage engine
     *
     * @var string
     */
    public $engine;

    /**
     * Table type
     *
     * @var	string
     */
    public $type;

    /**
     * Table length
     *
     * @var integer
     */
    public $length;

    /**
     * Table next auto increment value
     *
     * @var integer
     */
    public $autoinc;

    /**
     * The tables character set and collation
     *
     * @var string
     */
    public $collation;

    /**
     * The tables description
     *
     * @var string
     */
    public $description;

    /**
     * When the table was last updated
     *
     * @var timestamp
     */
    public $modified;

    /**
     * List of columns
     *
     * Associative array of columns, where key holds the columns name and the value is an DatabaseSchemaColumn
     * object.
     *
     * @var	array
     */
    public $columns = array();

    /**
     * List of behaviors
     *
     * Associative array of behaviors, where key holds the behavior identifier string and the value is an
     * DatabaseBehavior object.
     *
     * @var	array
     */
    public $behaviors = array();

    /**
     * List of indexes
     *
     * Associative array of indexes, where key holds the index name and the and the value is an object.
     *
     * @var	array
     */
    public $indexes = array();
}
