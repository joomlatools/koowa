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
 * Creatable Database Behavior
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Database\Behavior
 */
class DatabaseBehaviorCreatable extends DatabaseBehaviorAbstract
{
    /**
     * Get the user that created the resource
     *
     * @return UserInterface|null Returns a User object or NULL if no user could be found
     */
    public function getAuthor()
    {
        $user = null;

        if(!empty($this->created_by)) {
            $user = $this->getObject('user.provider')->getUser($this->created_by);
        }

        return $user;
    }

    /**
     * Check if the behavior is supported
     *
     * Behavior requires a 'created_by' or 'created_on' row property
     *
     * @return  boolean  True on success, false otherwise
     */
    public function isSupported()
    {
        $table = $this->getMixer();

        //Only check if we are connected with a table object, otherwise just return true.
        if($table instanceof DatabaseTableInterface)
        {
            if(!$table->hasColumn('created_by') && !$table->hasColumn('created_on'))  {
                return false;
            }
        }

        return true;
    }

    /**
     * Set created information
     *
     * Requires an 'created_on' and 'created_by' column
     *
     * @param DatabaseContext	$context A database context object
     * @return void
     */
    protected function _beforeInsert(DatabaseContext $context)
    {
        $mixer = $this->getMixer();
        $table = $mixer instanceof DatabaseRowInterface ?  $mixer->getTable() : $mixer;

        if(empty($this->created_by)) {
            $this->created_by  = (int) $this->getObject('user')->getId();
        }

        if(empty($this->created_on) || $this->created_on == $table->getDefault('created_on')) {
            $this->created_on  = gmdate('Y-m-d H:i:s');
        }
    }

    /**
     * Set created information
     *
     * Requires a 'created_by' column
     *
     * @param DatabaseContext	$context A database context object
     * @return void
     */
    protected function _afterSelect(DatabaseContext $context)
    {
        $rowset = $context->data;

        if($rowset instanceof DatabaseRowsetInterface)
        {
            $users = array();

            foreach($rowset as $row)
            {
                if(!empty($row->created_by)) {
                    $users[] = $row->created_by;
                }
            }

            //Lazy load the users
            $this->getObject('user.provider')->fetch($users, true);
        }
    }
}
