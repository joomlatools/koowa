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
 * Database Model
 *
 * Provides interaction with a database
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Model
 */
class ModelDatabase extends ModelAbstract
{
    /**
     * Table object or identifier
     *
     * @var string|object
     */
    protected $_table;

    /**
     * Constructor
     *
     * @param ObjectConfig $config  An optional ObjectConfig object with configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Set the table identifier
        $this->setTable($config->table);

        //Calculate the aliases based on the location of the table
        $model = $database = $this->getTable()->getIdentifier()->toArray();

        //Create database.rowset -> model.entity alias
        $database['path'] = array('database', 'rowset');
        $model['path']    = array('model'   , 'entity');

        $this->getObject('manager')->registerAlias($model, $database);

        //Create database.row -> model.entity alias
        $database['path'] = array('database', 'row');
        $database['name'] = StringInflector::singularize($database['name']);

        $model['path'] = array('model', 'entity');
        $model['name'] = StringInflector::singularize($model['name']);

        $this->getObject('manager')->registerAlias($model, $database);

        //Behavior depends on the database. Need to add if after database has been set.
        $this->addBehavior('indexable');

        //Create the query before fetch and count
        $this->addCommandCallback('before.fetch', '_buildQuery');
        $this->addCommandCallback('before.count', '_buildQuery');
    }

    /**
     * Initializes the config for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config An optional ObjectConfig object with configuration options
     * @return  void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'table'     => $this->getIdentifier()->name,
            'behaviors' => array('paginatable', 'sortable', 'sparsable'),
        ));

        parent::_initialize($config);
    }

    /**
     * Create a new entity for the data store
     *
     * @param ModelContextDatabase $context A model context object
     * @return  ModelEntityComposite The model entity
     */
    protected function _actionCreate(ModelContextDatabase $context)
    {
        //Get the data
        $data = ModelContext::unbox($context->properties);

        if(!is_numeric(key($data))) {
            $data = array($data);
        }

        //Entity options
        $options = array(
            'data'            => $data,
            'identity_column' => $context->getIdentityKey()
        );

        return $this->getTable()->createRowset($options);
    }

    /**
     * Fetch a new entity from the data store
     *
     * @param ModelContextDatabase $context A model context object
     * @return  ModelEntityComposite The model entity
     */
    protected function _actionFetch(ModelContextDatabase $context)
    {
        $table = $this->getTable();

        //Entity options
        $options = array(
            'identity_column' => $context->getIdentityKey()
        );

        //Select the rows
        if (!$context->state->isEmpty()) {
            $data = $table->select($context->query, Database::FETCH_ROWSET, $options);
        } else {
            $data = $table->createRowset($options);
        }

        return $data;
    }

    /**
     * Get the total number of entities
     *
     * @param ModelContext $context A model context object

     * @return string  The output of the view
     */
    protected function _actionCount(ModelContextDatabase $context)
    {
        return $this->getTable()->count($context->query);
    }

    /**
     * Method to get a table object
     *
     * @return DatabaseTableInterface
     */
    public function getTable()
    {
        if(!($this->_table instanceof DatabaseTableInterface)) {
            $this->_table = $this->getObject($this->_table);
        }

        return $this->_table;
    }

    /**
     * Method to set a table object attached to the model
     *
     * @param   mixed   $table An object that implements ObjectInterface, ObjectIdentifier object
     *                         or valid identifier string
     * @throws  \UnexpectedValueException   If the identifier is not a table identifier
     * @return  ModelDatabase
     */
    public function setTable($table)
    {
        if(!($table instanceof DatabaseTableInterface))
        {
            if(is_string($table) && strpos($table, '.') === false )
            {
                $identifier         = $this->getIdentifier()->toArray();
                $identifier['path'] = array('database', 'table');
                $identifier['name'] = StringInflector::pluralize(StringInflector::underscore($table));

                $identifier = $this->getIdentifier($identifier);
            }
            else  $identifier = $this->getIdentifier($table);

            if($identifier->path[1] != 'table') {
                throw new \UnexpectedValueException('Identifier: '.$identifier.' is not a table identifier');
            }

            $table = $identifier;
        }

        $this->_table = $table;

        return $this;
    }

    /**
     * Get the model context
     *
     * @return  ModelContext
     */
    public function getContext()
    {
        $context = new ModelContextDatabase(parent::getContext());
        $context->setQuery($this->getObject('lib:database.query.select'));
        return $context;
    }

    /**
     * Build the query
     *
     * @param ModelContextDatabase $context A model context object
     * @return  void
     */
    protected function _buildQuery(ModelContextDatabase $context)
    {
        //Initialise the query
        $context->query->table(array('tbl' => $this->getTable()->getName()));

        if($context->action == 'fetch')
        {
            $context->query->columns('tbl.*');

            $this->_buildQueryColumns($context->query);
            $this->_buildQueryJoins($context->query);
            $this->_buildQueryWhere($context->query);
            $this->_buildQueryGroup($context->query);
        }

        if($context->action == 'count')
        {
            $context->query->columns('COUNT(*)');

            $this->_buildQueryJoins($context->query);
            $this->_buildQueryWhere($context->query);
        }
    }

    /**
     * Builds SELECT columns list for the query
     *
     * @param DatabaseQueryInterface $query
     */
    protected function _buildQueryColumns(DatabaseQueryInterface $query)
    {

    }

    /**
     * Builds JOINS clauses for the query
     *
     * @param DatabaseQueryInterface $query
     */
    protected function _buildQueryJoins(DatabaseQueryInterface $query)
    {

    }

    /**
     * Builds WHERE clause for the query
     *
     * @param DatabaseQueryInterface $query
     */
    protected function _buildQueryWhere(DatabaseQueryInterface $query)
    {

    }

    /**
     * Builds GROUP BY clause for the query
     *
     * @param DatabaseQueryInterface $query
     */
    protected function _buildQueryGroup(DatabaseQueryInterface $query)
    {

    }
}