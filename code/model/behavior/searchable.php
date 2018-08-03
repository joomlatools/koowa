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
 * Searchable Model Behavior
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Model\Behavior
 */
class ModelBehaviorSearchable extends ModelBehaviorAbstract
{
    /**
     * The column names to search in
     *
     * Default is 'title'.
     *
     * @var array
     */
    protected $_columns;

    /**
     * Constructor.
     *
     * @param   ObjectConfig $config An optional ObjectConfig object with configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        $this->_columns = (array)ObjectConfig::unbox($config->columns);

        $this->addCommandCallback('before.fetch', '_buildQuery')
            ->addCommandCallback('before.count', '_buildQuery');
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   ObjectConfig $config An optional ObjectConfig object with configuration options
     *
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'columns' => 'title',
        ));

        parent::_initialize($config);
    }

    /**
     * Insert the model states
     *
     * @param ObjectMixable $mixer
     */
    public function onMixin(ObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('search', 'string');
    }

    /**
     * Add search query
     *
     * @param   ModelContextInterface $context A model context object
     *
     * @return    void
     */
    protected function _buildQuery(ModelContextInterface $context)
    {
        $model = $context->getSubject();

        if ($model instanceof ModelDatabase && !$context->state->isUnique())
        {
            $state  = $context->state;
            $search = $state->search;

            if ($search)
            {
                $search_column = null;
                $columns       = array_keys($this->getTable()->getColumns());
                
                // Parse $state->search for possible column prefix
                if (preg_match('#^([a-z0-9\-_]+)\s*:\s*(.+)\s*$#i', $search, $matches)) {
                    if (in_array($matches[1], $this->_columns) || $matches[1] === 'id') {
                        $search_column = $matches[1];
                        $search        = $matches[2];
                    }
                }
                
                // Search in the form of id:NUM
                if ($search_column === 'id') {
                    $context->query->where('(tbl.' . $this->getTable()->getIdentityColumn() . ' = :search)')
                        ->bind(array('search' => $search));
                }
                else
                {
                    $conditions = array();
                    foreach ($this->_columns as $column) {
                        if (in_array($column, $columns) && (!$search_column || $column === $search_column)) {
                            $conditions[] = 'tbl.' . $column . ' LIKE :search';
                        }
                    }
                    if ($conditions) {
                        $context->query->where('(' . implode(' OR ', $conditions) . ')')
                            ->bind(array('search' => '%' . $search . '%'));
                    }
                }
            }
        }
    }
}
