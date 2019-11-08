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
 * Abstract Controller Toolbar Decorator
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Controller\Toolbar
 */
abstract class ControllerToolbarDecorator extends ObjectDecorator implements ControllerToolbarInterface, CommandHandlerInterface
{
    /**
     * Command handler
     *
     * @param CommandInterface         $command    The command
     * @param CommandChainInterface    $chain      The chain executing the command
     * @return array|mixed Returns an array of the handler results in FIFO order. If a handler returns not NULL and the
     *                     returned value equals the break condition of the chain the break condition will be returned.
     */
    final public function execute(CommandInterface $command, CommandChainInterface $chain)
    {
        $parts  = explode('.', $command->getName());
        $method = '_'.$parts[0].ucfirst($parts[1]);

        $this->getDelegate()->execute($command, $chain);

        if(method_exists($this, $method)) {
            $this->$method($command);
        }
    }

    /**
     * Decorate Notifier
     *
     * Automatically attach the decorate toolbar if the delegate has previously already been attached. This will
     * subscribe the decorator to the event dispatcher.
     *
     * @param object $delegate The object being decorated
     * @return void
     * @throws  \InvalidArgumentException If the delegate is not an object
     * @see ControllerToolbarMixin::attachToolbar()
     */
    public function onDecorate($delegate)
    {
        $controller = $delegate->getController();

        if ($controller->isCommandable())
        {
            $type = $delegate->getType();

            if($controller->hasToolbar($type))
            {
                $controller->removeToolbar($delegate);
                $controller->addToolbar($this);
            }
        }

        parent::onDecorate($delegate);
    }

    /**
     * Get the toolbar's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getIdentifier()->name;
    }

    /**
     * Get the toolbar's title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getDelegate()->getTitle();
    }

    /**
     * Add a command
     *
     * @param   string    $command The command name
     * @param   mixed    $config  Parameters to be passed to the command
     * @return  ControllerToolbarCommand  The command that was added
     */
    public function addCommand($command, $config = array())
    {
        if (!($command instanceof ControllerToolbarCommand)) {
            $command = $this->getCommand($command, $config);
        }

        return $this->getDelegate()->addCommand($command, $config);
    }

    /**
     * Get a command by name
     *
     * @param string $name  The command name
     * @param array $config An optional associative array of configuration settings
     * @return mixed ControllerToolbarCommand if found, false otherwise.
     */
    public function getCommand($name, $config = array())
    {
        if(!$this->getDelegate()->hasCommand($name))
        {
            //Create the config object
            $command = new ControllerToolbarCommand($name, $config);

            //Attach the command to the toolbar
            $command->setToolbar($this);

            //Find the command function to call
            if (method_exists($this, '_command' . ucfirst($name)))
            {
                $function = '_command' . ucfirst($name);
                $this->$function($command);
            }
            else $this->getDelegate()->getCommand($name, $config);

        }
        else $command = $this->getDelegate()->getCommand($name, $config);

        return $command;
    }

    /**
     * Check if a command exists
     *
     * @param string $name  The command name
     * @return boolean True if the command exists, false otherwise.
     */
    public function hasCommand($name)
    {
        return $this->getDelegate()->hasCommand($name);
    }

    /**
     * Get the list of commands
     *
     * @return  array
     */
    public function getCommands()
    {
        return $this->getDelegate()->getCommands();
    }

    /**
     * Get the priority of the delegate
     *
     * @return	integer The event priority
     */
    public function getPriority()
    {
        return $this->getDelegate()->getPriority();
    }

    /**
     * Get a new iterator
     *
     * @return  \RecursiveArrayIterator
     */
    public function getIterator()
    {
        return $this->getDelegate()->getIterator();
    }

    /**
     * Returns the number of toolbar commands
     *
     * Required by the Countable interface
     *
     * @return int
     */
    public function count()
    {
        return $this->getDelegate()->count();
    }

    /**
     * Set the decorated model
     *
     * @param   ControllerToolbarInterface $delegate The decorated toolbar
     * @return  ControllerToolbarDecorator
     * @throws \InvalidArgumentException If the delegate is not a toolbar
     */
    public function setDelegate($delegate)
    {
        if (!$delegate instanceof ControllerToolbarInterface) {
            throw new \InvalidArgumentException('Delegate: '.get_class($delegate).' does not implement ControllerToolbarInterface');
        }

        return parent::setDelegate($delegate);
    }

    /**
     * Get the decorated toolbar
     *
     * @return ControllerToolbarInterface
     */
    public function getDelegate()
    {
        return parent::getDelegate();
    }
}