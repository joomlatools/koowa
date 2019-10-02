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
 * Commandable Controller Behavior
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Controller\Behavior
 */
class ControllerBehaviorCommandable extends ControllerBehaviorAbstract
{
    /**
     * List of toolbars
     *
     * The key holds the toolbar type name and the value the toolbar object
     *
     * @var    array
     */
    private $__toolbars = array();

    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param ObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        parent::_initialize($config);

        $config->append(array(
            'toolbars' => array(),
        ));
    }

    /**
     * Add the toolbars to the controller
     *
     * @param ControllerContext $context
     * @return void
     */
    protected function _beforeRender(ControllerContext $context)
    {
        $controller = $context->getSubject();

        //Add the template filter and inject the toolbars
        if($controller->getView() instanceof ViewTemplatable)
        {
            //Add the toolbars
            $toolbars = (array)ObjectConfig::unbox($this->getConfig()->toolbars);

            foreach ($toolbars as $key => $value)
            {
                if (is_numeric($key)) {
                    $this->addToolbar($value);
                } else {
                    $this->addToolbar($key, $value);
                }
            }

            $controller->getView()->getTemplate()->addFilter('toolbar', array('toolbars' => $this->getToolbars()));
        }
    }

    /**
     * Add a toolbar
     *
     * @param   mixed $toolbar An object that implements ObjectInterface, ObjectIdentifier object
     *                         or valid identifier string
     * @param  array   $config   An optional associative array of configuration settings
     * @return  Object The mixer object
     */
    public function addToolbar($toolbar, $config = array())
    {
        if (!($toolbar instanceof ControllerToolbarInterface))
        {
            if (!($toolbar instanceof ObjectIdentifier))
            {
                //Create the complete identifier if a partial identifier was passed
                if (is_string($toolbar) && strpos($toolbar, '.') === false)
                {
                    $identifier = $this->getIdentifier()->toArray();
                    $identifier['path'] = array('controller', 'toolbar');
                    $identifier['name'] = $toolbar;

                    $identifier = $this->getIdentifier($identifier);
                }
                else $identifier = $this->getIdentifier($toolbar);
            }
            else $identifier = $toolbar;

            $config['controller'] = $this->getMixer();
            $toolbar = $this->getObject($identifier, $config);
        }

        if (!($toolbar instanceof ControllerToolbarInterface)) {
            throw new \UnexpectedValueException("Controller toolbar $identifier does not implement ControllerToolbarInterface");
        }

        //Store the toolbar to allow for name lookups
        $this->__toolbars[$toolbar->getType()] = $toolbar;

        if ($this->inherits(__NAMESPACE__.'\CommandMixin')) {
            $this->addCommandHandler($toolbar);
        }

        return $this->getMixer();
    }

    /**
     * Remove a toolbar
     *
     * @param   ControllerToolbarInterface $toolbar A toolbar instance
     * @return  Object The mixer object
     */
    public function removeToolbar(ControllerToolbarInterface $toolbar)
    {
        if($this->hasToolbar($toolbar->getType()))
        {
            unset($this->__toolbars[$toolbar->getType()]);

            if ($this->inherits(__NAMESPACE__.'\CommandMixin')) {
                $this->removeCommandHandler($toolbar);
            }
        }

        return $this->getMixer();
    }

    /**
     * Check if a toolbar exists
     *
     * @param   string   $type The type of the toolbar
     * @return  boolean  TRUE if the toolbar exists, FALSE otherwise
     */
    public function hasToolbar($type)
    {
        return isset($this->__toolbars[$type]);
    }

    /**
     * Get a toolbar by type
     *
     * @param  string  $type   The toolbar type
     * @return ControllerToolbarInterface
     */
    public function getToolbar($type)
    {
        $result = null;

        if(isset($this->__toolbars[$type])) {
            $result = $this->__toolbars[$type];
        }

        return $result;
    }

    /**
     * Gets the toolbars
     *
     * @return array  An array of toolbars
     */
    public function getToolbars()
    {
        return array_values($this->__toolbars);
    }
}