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
 * Abstract Database Driver
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Database\Driver
 */
abstract class DatabaseDriverAbstract extends Object implements DatabaseDriverInterface, ObjectMultiton
{
    /**
     * Active state of the connection
     *
     * @var boolean
     */
    protected $_connected = null;

    /**
     * The database connection resource
     *
     * @var mixed
     */
    protected $_connection = null;

    /**
     * Last auto-generated insert_id
     *
     * @var integer
     */
    protected $_insert_id;

    /**
     * The affected row count
     *
     * @var int
     */
    protected $_affected_rows;

    /**
     * Schema cache
     *
     * @var array
     */
    protected $_table_schema = null;

    /**
     * The table prefix
     *
     * @var string
     */
    protected $_table_prefix = '';

    /**
     * The table needle
     *
     * @var string
     */
    protected $_table_needle = '';

    /**
     * Quote for named objects
     *
     * @var string
     */
    protected $_identifier_quote = '`';

    /**
     * Character set used for connection
     *
     * @var string
     */
    protected $_charset;

    /**
     * Constructor.
     *
     * @param   ObjectConfig $config Configuration options
     *
     * Recognized key values include 'command_chain', 'charset', 'table_prefix',
     * (this list is not meant to be comprehensive).
     */
    public function __construct(ObjectConfig $config = null)
    {
        parent::__construct($config);

        // Set the connection
        if (isset($config->connection)) {
            $this->setConnection($config->connection);
        }

        // Set the default charset. http://dev.mysql.com/doc/refman/5.1/en/charset-connection.html
        if (!empty($config->charset)) {
            $this->setCharset($config->charset);
        }

        // Set the table prefix
        $this->_table_prefix = $config->table_prefix;

        // Set the table prefix
        $this->_table_needle = $config->table_needle;

        // Mixin the command interface
        $this->mixin('lib:command.mixin', $config);

        //Auto connect to the database
        if($config->auto_connect) {
            $this->connect();
        }
    }

    /**
     * Destructor
     *
     * Free any resources that are open.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   ObjectConfig $config Configuration options
     * @return  void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'charset'       => 'UTF-8',
            'table_prefix'  => null,
            'table_needle'  => null,
            'command_chain' => 'lib:command.chain',
            'connection'    => null,
            'auto_connect'  => false,
        ));

        parent::_initialize($config);
    }

    /**
     * Reconnect to the db
     *
     * @return  DatabaseDriverAbstract
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();

        return $this;
    }

    /**
     * Disconnect from db
     *
     * @return  DatabaseDriverAbstract
     */
    public function disconnect()
    {
        $this->_connection = null;
        $this->_connected  = false;

        return $this;
    }

    /**
     * Get the database name
     *
     * @return string	The database name
     */
    abstract function getDatabase();

    /**
     * Set the database name
     *
     * @param 	string 	$database The database name
     * @return  DatabaseDriverAbstract
     */
    abstract function setDatabase($database);

    /**
     * Get the connection
     *
     * Provides access to the underlying database connection. Useful for when
     * you need to call a proprietary method such as postgresql's lo_* methods
     *
     * @param bool $auto_connect If connection hasn't been established, auto connect
     * @return resource
     * @throws \RuntimeException if auto connection fails
     */
    public function getConnection($auto_connect = true)
    {
        if (!$this->isConnected() && $auto_connect) {
            $this->connect();

            if (!$this->isConnected()) {
                throw new \RuntimeException('Database auto connection failed');
            }
        }

        return $this->_connection;
    }

    /**
     * Set the connection
     *
     * @param 	resource    $resource The connection resource
     * @return  DatabaseDriverAbstract
     */
    public function setConnection($resource)
    {
        $this->_connection = $resource;
        return $this;
    }

    /**
     * Get character set
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Set character set
     *
     * @param string $charset The character set.
     * @return DatabaseDriverAbstract
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;

        return $this;
    }

    /**
     * Get the insert id of the last insert operation
     *
     * @return mixed The id of the last inserted row(s)
     */
    public function getInsertId()
    {
        return $this->_insert_id;
    }

    /**
     * Performs a select query.
     *
     * Use for SELECT and anything that returns rows.
     *
     * @param   DatabaseQueryInterface $query The query object.
     * @param   integer              $mode  The fetch mode. Controls how the result will be returned to the subject.
     *                                      This value must be one of the Database::FETCH_* constants.
     * @param   string               $key   The column name of the index to use.
     * @throws  \InvalidArgumentException If the query is not an instance of DatabaseQuerySelect or DatabaseQueryShow
     * @return  mixed     The return value of this function on success depends on the fetch type.
     *                    In all cases, FALSE is returned on failure.
     */
    public function select(DatabaseQueryInterface $query, $mode = Database::FETCH_ARRAY_LIST, $key = '')
    {
        if (!$query instanceof DatabaseQuerySelect && !$query instanceof DatabaseQueryShow) {
            throw new \InvalidArgumentException('Query must be an instance of DatabaseQuerySelect or DatabaseQueryShow');
        }

        $context  = $this->getContext();
        $context->query = $query;
        $context->mode  = $mode;

        // Execute the insert operation
        if ($this->invokeCommand('before.select', $context) !== false)
        {
            if ($result = $this->execute($context->query, Database::RESULT_USE))
            {
                switch ($context->mode)
                {
                    case Database::FETCH_ROW         :
                    case Database::FETCH_ARRAY       :
                        $context->result = $this->_fetchArray($result);
                        break;

                    case Database::FETCH_ROWSET      :
                    case Database::FETCH_ARRAY_LIST  :
                        $context->result = $this->_fetchArrayList($result, $key);
                        break;

                    case Database::FETCH_FIELD       :
                        $context->result = $this->_fetchField($result, $key);
                        break;

                    case Database::FETCH_FIELD_LIST  :
                        $context->result = $this->_fetchFieldList($result, $key);
                        break;

                    case Database::FETCH_OBJECT      :
                        $context->result = $this->_fetchObject($result);
                        break;

                    case Database::FETCH_OBJECT_LIST :
                        $context->result = $this->_fetchObjectList($result, $key);
                        break;

                    default :
                        $result->free();
                }
            }

            $this->invokeCommand('after.select', $context);
        }

        return ObjectConfig::unbox($context->result);
    }

    /**
     * Insert a row of data into a table.
     *
     * @param DatabaseQueryInsert $query The query object.
     * @return bool|integer  If the insert query was executed returns the number of rows updated, or 0 if
     *                          no rows where updated, or -1 if an error occurred. Otherwise FALSE.
     */
    public function insert(DatabaseQueryInsert $query)
    {
        $context = $this->getContext();
        $context->query = $query;

        //Execute the insert operation
        if ($this->invokeCommand('before.insert', $context) !== false)
        {
            //Check if we have valid data to insert, if not return false
            if ($context->query->values)
            {
                //Execute the query
                $context->result = $this->execute($context->query);
                $context->affected = $this->_affected_rows;

                $this->invokeCommand('after.insert', $context);
            }
            else $context->affected = false;
        }

        return $context->affected;
    }

    /**
     * Update a table with specified data.
     *
     * @param  DatabaseQueryUpdate $query The query object.
     * @return integer  If the update query was executed returns the number of rows updated, or 0 if
     *                     no rows where updated, or -1 if an error occurred. Otherwise FALSE.
     */
    public function update(DatabaseQueryUpdate $query)
    {
        $context = $this->getContext();
        $context->query = $query;

        //Execute the update operation
        if ($this->invokeCommand('before.update', $context) !== false)
        {
            if (!empty($context->query->values))
            {
                //Execute the query
                $context->result   = $this->execute($context->query);
                $context->affected = $this->_affected_rows;

                $this->invokeCommand('after.update', $context);
            }
            else $context->affected = false;
        }

        return $context->affected;
    }

    /**
     * Delete rows from the table.
     *
     * @param  DatabaseQueryDelete $query The query object.
     * @return integer     Number of rows affected, or -1 if an error occurred.
     */
    public function delete(DatabaseQueryDelete $query)
    {
        $context = $this->getContext();
        $context->query = $query;

        //Execute the delete operation
        if ($this->invokeCommand('before.delete', $context) !== false)
        {
            //Execute the query
            $context->result = $this->execute($context->query);
            $context->affected = $this->_affected_rows;

            $this->invokeCommand('after.delete', $context);
        }

        return $context->affected;
    }

    /**
     * Use and other queries that don't return rows
     *
     * @param  string      $query The query to run. Data inside the query should be properly escaped.
     * @param  integer     $mode  The result made, either the constant Database::RESULT_USE, Database::RESULT_STORE
     *                     or Database::MULTI_QUERY depending on the desired behavior.
     *                     By default, Database::RESULT_STORE is used. If you use Database::RESULT_USE all subsequent
     *                     calls will return error Commands out of sync unless you free the result first.
     * @throws \RuntimeException If the query could not be executed
     * @return mixed       For SELECT, SHOW, DESCRIBE or EXPLAIN will return a result object.
     *                     For other successful queries  return TRUE.
     */
    public function execute($query, $mode = Database::RESULT_STORE)
    {
        // Add or replace the database table prefix.
        if (!($query instanceof DatabaseQueryInterface)) {
            $query = $this->replaceTableNeedle($query);
        }

        $result = $this->_executeQuery($query, $mode);

        if ($result === false)
        {
            throw new \RuntimeException(
                $this->getConnection()->error . ' of the following query : ' . $query, $this->getConnection()->errno
            );
        }

        $this->_affected_rows = $this->getConnection()->affected_rows;
        $this->_insert_id = $this->getConnection()->insert_id;

        return $result;
    }

    /**
     * Set the table prefix
     *
     * @param string $prefix The table prefix
     * @return DatabaseDriverAbstract
     * @see DatabaseDriverAbstract::replaceTableNeedle
     */
    public function setTablePrefix($prefix)
    {
        $this->_table_prefix = $prefix;
        return $this;
    }

    /**
     * Get the table prefix
     *
     * @return string The table prefix
     * @see DatabaseDriverAbstract::replaceTableNeedle
     */
    public function getTablePrefix()
    {
        return $this->_table_prefix;
    }

    /**
     * Get the table needle
     *
     * @return string The table needle
     * @see DatabaseDriverAbstract::replaceTableNeedle
     */
    public function getTableNeedle()
    {
        return $this->_table_needle;
    }

    /**
     * Get a database context object
     *
     * @return  DatabaseContext
     */
    public function getContext()
    {
        $context = new DatabaseContext();
        $context->setSubject($this);

        return $context;
    }

    /**
     * This function replaces the table needles in a query string with the actual table prefix.
     *
     * @param  string      $sql     The SQL query string
     * @param  string|null $replace Table prefix, null for default
     * @return string       The SQL query string
     */
    public function replaceTableNeedle($sql, $replace = null)
    {
        if($needle  = $this->getTableNeedle())
        {
            $replace = isset($replace) ? $replace : $this->getTablePrefix();
            $sql     = trim( $sql );

            $pattern = "($needle(?=[a-z0-9]))";
            $sql = preg_replace($pattern, $replace, $sql);
        }

        return $sql;
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted and then returned as a comma-separated string;
     * this is useful for generating IN() lists.
     *
     * @param   mixed $value The value to quote.
     * @return string An SQL-safe quoted value (or a string of separated-and-quoted values).
     */
    public function quoteValue($value)
    {
        if (is_array($value))
        {
            //Quote array values, not keys, then combine with commas.
            foreach ($value as &$v)
            {
                if (is_null($v)) {
                    $v = 'NULL';
                } elseif (is_string($v)) {
                    $v = $this->_quoteValue($v);
                }
            }

            $value = implode(', ', $value);
        }
        else
        {
            if (is_null($value)) {
                $value = 'NULL';
            } elseif (is_string($value)) {
                $value = $this->_quoteValue($value);
            }
        }

        return $value;
    }

    /**
     * Quotes a single identifier name (table, table alias, table column, index, sequence).  Ignores empty values.
     *
     * This function requires all SQL statements, operators and functions to be uppercase.
     *
     * @param string|array $spec The identifier name to quote.  If an array, quotes each element in the array as an
     *                           identifier name.
     * @return string|array The quoted identifier name (or array of names).
     *
     * @see _quoteIdentifier()
     */
    public function quoteIdentifier($spec)
    {
        if (is_array($spec))
        {
            foreach ($spec as $key => $val) {
                $spec[$key] = $this->quoteIdentifier($val);
            }

            return $spec;
        }

        // String spaces around the identifier
        $spec = trim($spec);

        // Quote all the lower case parts
        $spec = preg_replace_callback('/(?:\b|#)+(?<![`:@%])([-a-zA-Z0-9.#_]*[a-z][-a-zA-Z0-9.#_]*)(?!`)\b/', array($this, '_quoteIdentifier'), $spec);

        return $spec;
    }

    /**
     * Execute a query
     *
     * @param  string      $query The query to run. Data inside the query should be properly escaped.
     * @param  integer     $mode  The result made, either the constant Database::RESULT_USE, Database::RESULT_STORE
     *                            or Database::MULTI_QUERY depending on the desired behavior.
     * @return mixed       For SELECT, SHOW, DESCRIBE or EXPLAIN will return a result object.
     *                     For other successful queries  return TRUE.
     */
    abstract protected function _executeQuery($query, $mode = Database::RESULT_STORE);

   /**
     * Fetch the first field of the first row
     *
     * @param   \mysqli_result   $result The result object. A result set identifier returned by the select() function
     * @param   integer         $key    The index to use
     * @return  mixed           The value returned in the query or null if the query failed.
     */
    abstract protected function _fetchField($result, $key = 0);

    /**
     * Fetch an array of single field results
     *
     * @param   \mysqli_result   $result The result object. A result set identifier returned by the select() function
     * @param   integer         $key    The index to use
     * @return  array           A sequential array of returned rows.
     */
    abstract protected function _fetchFieldList($result, $key = 0);

    /**
     * Fetch the first row of a result set as an associative array
     *
     * @param   \mysqli_result   $sql The result object. A result set identifier returned by the select() function
     * @return array
     */
    abstract protected function _fetchArray($sql);

    /**
     * Fetch all result rows of a result set as an array of associative arrays
     *
     * If <var>key</var> is not empty then the returned array is indexed by the value of the database key.
     * Returns <var>null</var> if the query fails.
     *
     * @param   \mysqli_result   $result The result object. A result set identifier returned by the select() function
     * @param   string          $key    The column name of the index to use
     * @return  array   If key is empty as sequential list of returned records.
     */
    abstract protected function _fetchArrayList($result, $key = '');

    /**
     * Fetch the first row of a result set as an object
     *
     * @param   \mysqli_result  $result The result object. A result set identifier returned by the select() function
     * @param object
     */
    abstract protected function _fetchObject($result);

    /**
     * Fetch all rows of a result set as an array of objects
     *
     * If <var>key</var> is not empty then the returned array is indexed by the value of the database key.
     * Returns <var>null</var> if the query fails.
     *
     * @param   \mysqli_result  $result The result object. A result set identifier returned by the select() function
     * @param   string         $key    The column name of the index to use
     * @return  array   If <var>key</var> is empty as sequential array of returned rows.
     */
    abstract protected function _fetchObjectList($result, $key='' );

    /**
     * Parse the raw table schema information
     *
     * @param   object  $info The raw table schema information
     * @return DatabaseSchemaTable
     */
    abstract protected function _parseTableInfo($info);

    /**
     * Parse the raw column schema information
     *
     * @param   object  $info The raw column schema information
     * @return DatabaseSchemaColumn
     */
    abstract protected function _parseColumnInfo($info);

    /**
     * Given a raw column specification, parse into datatype, size, and decimal scope.
     *
     * @param string $spec The column specification; for example, "VARCHAR(255)" or "NUMERIC(10,2)".
     * @return array A sequential array of the column type, size, and scope.
     */
    abstract protected function _parseColumnType($spec);

    /**
     * Safely quotes a value for an SQL statement.
     *
     * @param   mixed   $value The value to quote
     * @return string An SQL-safe quoted value
     */
    abstract protected function _quoteValue($value);

    /**
     * Quotes an identifier name (table, index, etc). Ignores empty values.
     *
     * If the name contains a dot, this method will separately quote the parts before and after the dot.
     *
     * @param string    $name The identifier name to quote.
     * @return string   The quoted identifier name.
     * @see quoteIdentifier()
     */
    protected function _quoteIdentifier($name)
    {
        if (is_array($name)) {
            $name = $name[0];
        }

        $name = trim($name);

        //Special cases
        if ($name == '*' || is_numeric($name)) {
            return $name;
        }

        if ($pos = strrpos($name, '.'))
        {
            $table = $this->_quoteIdentifier(substr($name, 0, $pos));
            $column = $this->_quoteIdentifier(substr($name, $pos + 1));

            $result = "$table.$column";
        }
        else $result = $this->_identifier_quote . $name . $this->_identifier_quote;

        return $result;
    }
}
