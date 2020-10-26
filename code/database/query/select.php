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
 * Select Database Query
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Database\Query
 */
class DatabaseQuerySelect extends DatabaseQueryAbstract
{
    /**
     * Distinct operation
     *
     * @var boolean
     */
    public $distinct  = false;

    /**
     * Shuffle operation
     *
     * @var boolean
     */
    public $shuffle  = false;

    /**
     * The columns
     *
     * @var array
     */
    public $columns = array();

    /**
     * The table element
     *
     * @var array
     */
    public $table = array();

    /**
     * The join element
     *
     * @var array
     */
    public $join = array();

    /**
     * The where element
     *
     * @var array
     */
    public $where = array();

    /**
     * The group element
     *
     * @var array
     */
    public $group = array();

    /**
     * The having element
     *
     * @var array
     */
    public $having = array();

    /**
     * The order element
     *
     * @var array
     */
    public $order = array();

    /**
     * The limit element
     *
     * @var integer
     */
    public $limit = null;

    /**
     * The limit offset element
     *
     * @var integer
     */
    public $offset = null;

    /**
     * Checks if the current query is a count query.
     *
     * @return boolean
     */
    public function isCountQuery()
    {
        $columns = $this->columns;
        return $columns && current($columns) == 'COUNT(*)';
    }

    /**
     * Checks if the current query is a distinct query.
     *
     * @return boolean
     */
    public function isDistinctQuery()
    {
        return (bool) $this->distinct;
    }

    /**
     * Make the query distinct
     *
     * @return DatabaseQuerySelect
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Build a select query
     *
     * @param  array|string $columns A string or an array of column names
     * @return DatabaseQuerySelect
     */
    public function columns($columns = array())
    {
        foreach ((array) $columns as $key => $value)
        {
            if (is_string($key)) {
                $this->columns[$key] = $value;
            } else {
                $this->columns[] = $value;
            }
        }

        return $this;
    }

    /**
     * Build the from clause
     *
     * @param  array|string The table string or array name.
     * @return DatabaseQuerySelect
     */
    public function table($table)
    {
        $this->table = (array) $table;
        return $this;
    }

    /**
     * Build the join clause
     *
     * @param string $table      The table name to join to.
     * @param string $condition  The join condition statement.
     * @param string|array $type The type of join; empty for a plain JOIN, or "LEFT", "INNER", etc.
     * @return DatabaseQuerySelect
     */
    public function join($table, $condition = null, $type = 'LEFT')
    {
        settype($table, 'array');

        $data = array(
            'table'     => current($table),
            'condition' => $condition,
            'type'      => $type
        );

        if (is_string(key($table))) {
            $this->join[key($table)] = $data;
        } else {
            $this->join[] = $data;
        }

        return $this;
    }

    /**
     * Build the where clause
     *
     * @param   string $condition   The where condition statement
     * @param   string $combination The where combination, defaults to 'AND'
     * @return  DatabaseQuerySelect
     */
    public function where($condition, $combination = 'AND')
    {
        $this->where[] = array(
            'condition'   => $condition,
            'combination' => count($this->where) ? $combination : ''
        );

        return $this;
    }

    /**
     * Build the group clause
     *
     * @param   array|string $columns A string or array of ordering columns
     * @return  DatabaseQuerySelect
     */
    public function group($columns)
    {
        $this->group = array_unique(array_merge($this->group, (array) $columns));
        return $this;
    }

    /**
     * Build the having clause
     *
     * @param   string $condition   The having condition statement
     * @param   string $combination The having combination, defaults to 'AND'
     *
     * @return  DatabaseQuerySelect
     */
    public function having($condition, $combination = 'AND')
    {
        $this->having[] = array(
            'condition'   => $condition,
            'combination' => count($this->having) ? $combination : ''
        );

        return $this;
    }

    /**
     * Build the order clause
     *
     * @param   array|string $columns   A string or array of ordering columns
     * @param   string       $direction Either DESC or ASC
     * @return  DatabaseQuerySelect
     */
    public function order($columns, $direction = 'ASC')
    {
        foreach ((array) $columns as $column)
        {
            $this->order[] = array(
                'column'    => $column,
                'direction' => $direction
            );
        }

        return $this;
    }

    /**
     * Build the shuffle clause
     *
     * @return  $this
     */
    public function shuffle()
    {
        $this->shuffle = true;
        return $this;
    }

    /**
     * Build the limit element
     *
     * @param   integer $limit  Number of items to fetch.
     * @param   integer $offset Offset to start fetching at.
     * @return  DatabaseQuerySelect
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit  = (int) $limit;
        $this->offset = (int) $offset;

        return $this;
    }

    /**
     * Render the query to a string
     *
     * @return  string  The completed query
     */
    public function toString()
    {
        $driver = $this->getDriver();
        $prefix = $driver->getTablePrefix();
        $query  = 'SELECT';

        if($this->columns)
        {
            if($this->distinct) {
                $query .= ' DISTINCT';
            }

            $columns = array();
            foreach($this->columns as $alias => $column)
            {
                if($column instanceof DatabaseQuerySelect) {
                    $columns[] = '('.$column.')'.(is_string($alias) ? ' AS '.$driver->quoteIdentifier($alias) : '');
                } else {
                    $columns[] = $driver->quoteIdentifier($column.(is_string($alias) ? ' AS '.$alias : ''));
                }
            }

            $query .= ' '.implode(', ', $columns);
        }
        else $query .= ' *';

        if($this->table)
        {
            if(current($this->table) instanceof DatabaseQuerySelect) {
                $table= '('.current($this->table).')'.(!is_numeric(key($this->table)) ? ' AS '.$driver->quoteIdentifier(key($this->table)) : '');
            } else {
                $table = $driver->quoteIdentifier($prefix.current($this->table).(!is_numeric(key($this->table)) ? ' AS '.key($this->table) : ''));
            }

            $query .= ' FROM '.$table;
        }

        if($this->join)
        {
            $joins = array();
            foreach($this->join as $alias => $join)
            {
                $tmp = '';

                if($join['type']) {
                    $tmp .= ' '.$join['type'];
                }

                if($join['table'] instanceof DatabaseQuerySelect) {
                    $tmp .= ' JOIN ('.$join['table'].')'.(is_string($alias) ? ' AS '.$driver->quoteIdentifier($alias) : '');
                } else {
                    $tmp .= ' JOIN '.$driver->quoteIdentifier($prefix.$join['table'].(is_string($alias) ? ' AS '.$alias : ''));
                }

                if($join['condition']) {
                    $tmp .= ' ON ('.$driver->quoteIdentifier($join['condition']).')';
                }

                $joins[] = $tmp;
            }

            $query .= implode('', $joins);
        }

        if($this->where)
        {
            $query .= ' WHERE';

            foreach($this->where as $where)
            {
                if($where['combination']) {
                    $query .= ' '.$where['combination'];
                }

                $query .= ' '. $driver->quoteIdentifier($where['condition']);
            }
        }

        if($this->group)
        {
            $columns = array();
            foreach($this->group as $column) {
                $columns[] = $driver->quoteIdentifier($column);
            }

            $query .= ' GROUP BY '.implode(' , ', $columns);
        }

        if($this->having)
        {
            $query .= ' HAVING';

            foreach($this->having as $having)
            {
                if($having['combination']) {
                    $query .= ' '.$having['combination'];
                }

                $query .= ' '. $driver->quoteIdentifier($having['condition']);
            }
        }

        if($this->order || $this->shuffle)
        {
            $query .= ' ORDER BY ';

            $list = array();

            if($this->shuffle) {
                $list[] = 'RAND()';
            }

            foreach($this->order as $order) {
                $list[] = $driver->quoteIdentifier($order['column']).' '.$order['direction'];
            }

            $query .= implode(' , ', $list);
        }

        if($this->limit) {
            $query .= ' LIMIT '.$this->offset.' , '.$this->limit;
        }

        if($this->_parameters) {
            $query = $this->_replaceParams($query);
        }

        return $query;
    }
}
