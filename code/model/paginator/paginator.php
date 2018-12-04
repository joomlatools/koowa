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
 * Paginator Model
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Model\Paginator
 */
class ModelPaginator extends ObjectConfig implements ModelPaginatorInterface
{
    /**
     * Get the pages
     *
     * @return ObjectConfig A ObjectConfig object that holds the page information
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Set a configuration element
     *
     * @param  string
     * @param  mixed
     * @return void
     */
    public function set($name, $value)
    {
        parent::set($name, $value);

        //Only calculate the limit and offset if we have a total
        if($this->total)
        {
            $this->limit  = (int) max($this->limit, 0);
            $this->offset = (int) max($this->offset, 0);

            if($this->limit > $this->total) {
                $this->offset = 0;
            }

            if(!$this->limit)
            {
                $this->offset = 0;
                $this->limit  = $this->total;
            }

            $this->count  = (int) ceil($this->total / $this->limit);

            if($this->offset > $this->total) {
                $this->offset = ($this->count-1) * $this->limit;
            }

            $this->current = (int) floor($this->offset / $this->limit) + 1;
        }
    }

    /**
     * Implements lazy loading of the pages config property.
     *
     * @param string  $name
     * @param mixed   $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if($name == 'pages' && !isset($this->pages)) {
            $this->pages = $this->_pages();
        }

        return parent::get($name);
    }

    /**
     * Get a list of pages
     *
     * @return  ObjectConfig   Returns and array of pages information
     */
    protected function _pages()
    {
        $pages = new ObjectConfig();
        $current  = ($this->current - 1) * $this->limit;

        // First
        $offset = 0;
        $active  = $offset != $this->offset;
        $pages->first = array('title' => 'First', 'page' => 1, 'offset' => $offset, 'limit' => $this->limit, 'current' => false, 'active' => $active);

        // Previous
        $offset = max(0, ($this->current - 2) * $this->limit);
        $active  = $offset != $this->offset;
        $pages->previous = array('title' => '&laquo;', 'page' => $this->current - 1, 'offset' => $offset, 'limit' => $this->limit, 'rel' => 'prev', 'current' => false, 'active' => $active);

        // Pages
        $offsets = array();
        foreach($this->_offsets() as $page => $offset)
        {
            $current = $offset == $this->offset;
            $offsets[] = array('title' => $page, 'page' => $page, 'offset' => $offset, 'limit' => $this->limit, 'current' => $current, 'active' => !$current);
        }

        $pages->offsets = $offsets;

        // Next
        $offset = min(($this->count-1) * $this->limit, ($this->current) * $this->limit);
        $active  = $offset != $this->offset;
        $pages->next = array('title' => '&raquo;', 'page' => $this->current + 1, 'offset' => $offset, 'limit' => $this->limit, 'rel' => 'next', 'current' => false, 'active' => $active);

        // Last
        $offset = ($this->count - 1) * $this->limit;
        $active  = $offset != $this->offset;
        $pages->last = array('title' => 'Last', 'page' => $this->count, 'offset' => $offset, 'limit' => $this->limit, 'current' => false, 'active' => $active);

        return $pages;
    }

    /**
     * Get the offset for each page, optionally with a range
     *
     * @return  array   Page number => offset
     */
    protected function _offsets()
    {
        if($display = $this->display)
        {
            $start  = min($this->count, (int) max($this->current - $display, 1));
            $stop   = (int) min($this->current + $display, $this->count);

            $pages = range($start, $stop);

            if ($this->current > 2) {
                array_unshift($pages, 1, 2);
            }

            if ($this->count - $this->current > 2) {
                array_push($pages, $this->count-1, $this->count);
            }
        }
        else $pages = range(1, $this->count);

        $result = array();
        foreach($pages as $pagenumber) {
            $result[$pagenumber] =  ($pagenumber-1) * $this->limit;
        }

        return $result;
    }
}