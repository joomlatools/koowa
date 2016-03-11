<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2007 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/nooku/nooku-framework for the canonical source repository
 */

/**
 * Sortable Model Behavior
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Model\Behavior
 */
class KModelBehaviorSortable extends KModelBehaviorAbstract
{
    /**
     * Insert the model states
     *
     * @param KObjectMixable $mixer
     */
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('sort', 'cmd')
            ->insert('direction', 'word', 'asc');
    }

    /**
     * Split the sort state if format is [column,ASC|DESC]
     *
     * @param   KModelContextInterface $context A model context object
     * @return  void
     */
    protected function _afterReset(KModelContextInterface $context)
    {
        if($context->modified == 'sort' && strpos($context->state->sort, ',') !== false)
        {
            $context->state->sort = explode(',', $context->state->sort);

            foreach($context->state->sort as $key => $value)
            {
                if(strtoupper($value) == 'DESC' || strtoupper($value) == 'ASC')
                {
                    unset($context->state->sort[$key]);
                    $context->state->direction = $value;
                }
            }
        }
    }

    /**
     * Add order query
     *
     * @param   KModelContextInterface $context A model context object
     * @return  void
     */
    protected function _beforeFetch(KModelContextInterface $context)
    {
        $model = $context->getSubject();

        if ($model instanceof KModelDatabase && !$context->state->isUnique())
        {
            $state = $context->state;

            $sort      = trim($state->sort);
            $direction = strtoupper($state->direction);
            $columns   = array_keys($this->getTable()->getColumns());

            if ($sort)
            {
                $column = $this->getTable()->mapColumns($sort);
                $context->query->order($column, $direction);
            }

            if ($sort != 'ordering' && in_array('ordering', $columns)) {
                $context->query->order('tbl.ordering', 'ASC');
            }
        }
    }
}