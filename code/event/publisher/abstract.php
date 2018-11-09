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
 * Abstract Event Publisher
 *
 * Implementation provides a topic based event publishing mechanism. Higher priority event listeners are called first.
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Event\Publisher
 */
abstract class EventPublisherAbstract extends Object implements EventPublisherInterface
{
    /**
     * List of event listeners
     *
     * @var array
     */
    private $__listeners;

    /**
     * Enabled status of the publisher
     *
     * @var boolean
     */
    private $__enabled = true;

    /**
     * Constructor.
     *
     * @param ObjectConfig $config  An optional ObjectConfig object with configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        $this->__listeners = array();

        $this->__enabled = $config->enabled;
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
            'enabled' => true,
        ));

        parent::_initialize($config);
    }

    /**
     * Enable the publisher
     *
     * @return  EventPublisherAbstract
     */
    public function enable()
    {
        $this->__enabled = true;
        return $this;
    }

    /**
     * Disable the publisher
     *
     * @return  EventPublisherAbstract
     */
    public function disable()
    {
        $this->__enabled = false;
        return $this;
    }

    /**
     * Publish an event by calling all listeners that have registered to receive it.
     *
     * @param  string|EventInterface             $event      The event name or a EventInterface object
     * @param  array|Traversable|EventInterface  $attributes An associative array, an object implementing the
     *                                                        EventInterface or a Traversable object
     * @param  mixed                              $target     The event target
     * @throws \InvalidArgumentException  If the event is not a string or does not implement the EventInterface
     * @return null|EventInterface Returns the event object. If the chain is not enabled will return NULL.
     */
    public function publishEvent($event, $attributes = array(), $target = null)
    {
        if ($this->isEnabled())
        {
            if(!is_string($event) && !$event instanceof EventInterface)
            {
                throw new \InvalidArgumentException(
                    'The event must be a string or implement the EventInterface, "'.gettype($event).'" given.'
                );
            }

            //Make sure we have an event object
            if (!$event instanceof EventInterface)
            {
                if($attributes instanceof EventInterface)
                {
                    $name  = $event;
                    $event = $attributes;

                    $event->setName($name);
                }
                else $event = new Event($event, $attributes, $target);
            }

            //Instantiate the subscribers
            $this->getObject('event.subscriber.factory')->subscribeEvent($event->getName(), $this);

            //Notify the listeners
            $listeners = $this->getListeners($event->getName());

            foreach ($listeners as $listener)
            {
                call_user_func($listener, $event, $this);

                if (!$event->canPropagate()) {
                    break;
                }
            }

            return $event;
        }

        return null;
    }

    /**
     * Add an event listener
     *
     * @param string|EventInterface  $event     The event name or a EventInterface object
     * @param callable                $listener  The listener
     * @param integer                 $priority  The event priority, usually between 1 (high priority) and 5 (lowest),
     *                                            default is 3 (normal)
     * @throws \InvalidArgumentException If the listener is not a callable
     * @throws \InvalidArgumentException  If the event is not a string or does not implement the EventInterface
     * @return EventPublisherAbstract
     */
    public function addListener($event, $listener, $priority = Event::PRIORITY_NORMAL)
    {
        if (!is_callable($listener))
        {
            throw new \InvalidArgumentException(
                'The listener must be a callable, "'.gettype($listener).'" given.'
            );
        }

        if (!is_string($event) && !$event instanceof EventInterface)
        {
            throw new \InvalidArgumentException(
                'The event must be a string or implement the EventInterface, "'.gettype($event).'" given.'
            );
        }

        if($event instanceof EventInterface) {
            $event = $event->getName();
        }

        $this->__listeners[$event][$priority][] = $listener;

        ksort($this->__listeners[$event]);
        return $this;
    }

    /**
     * Remove an event listener
     *
     * @param string|EventInterface  $event     The event name or a EventInterface object
     * @param callable                $listener  The listener
     * @throws \InvalidArgumentException If the listener is not a callable
     * @throws \InvalidArgumentException  If the event is not a string or does not implement the EventInterface
     * @return EventPublisherAbstract
     */
    public function removeListener($event, $listener)
    {
        if (!is_callable($listener))
        {
            throw new \InvalidArgumentException(
                'The listener must be a callable, "'.gettype($listener).'" given.'
            );
        }

        if (!is_string($event) && !$event instanceof EventInterface)
        {
            throw new \InvalidArgumentException(
                'The event must be a string or implement the EventInterface, "'.gettype($event).'" given.'
            );
        }

        if($event instanceof EventInterface) {
            $event = $event->getName();
        }

        if (isset($this->__listeners[$event]))
        {
            foreach ($this->__listeners[$event] as $priority => $listeners)
            {
                if (false !== ($key = array_search($listener, $listeners))) {
                    unset($this->__listeners[$event][$priority][$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Get a list of listeners for a specific event
     *
     * @param string|EventInterface  $event     The event name or a EventInterface object
     * @throws \InvalidArgumentException  If the event is not a string or does not implement the EventInterface
     * @return array An array containing the listeners ordered by priority
     */
    public function getListeners($event)
    {
        $result = array();

        if (!is_string($event) && !$event instanceof EventInterface)
        {
            throw new \InvalidArgumentException(
                'The event must be a string or implement the EventInterface, "'.gettype($event).'" given.'
            );
        }

        if($event instanceof EventInterface) {
            $event = $event->getName();
        }

        if (isset($this->__listeners[$event]))
        {
            foreach($this->__listeners[$event] as $priority => $listeners) {
                $result = array_merge($result, $listeners);
            }
        }

        return $result;
    }

    /**
     * Set the priority of an event
     *
     * @param  string|EventInterface  $event     The event name or a EventInterface object
     * @param  callable                $listener  The listener
     * @param  integer                 $priority  The event priority
     * @throws \InvalidArgumentException If the listener is not a callable
     * @throws \InvalidArgumentException If the event is not a string or does not implement the EventInterface
     * @return EventPublisherAbstract
     */
    public function setListenerPriority($event, $listener, $priority)
    {
        if (!is_callable($listener))
        {
            throw new \InvalidArgumentException(
                'The listener must be a callable, "'.gettype($listener).'" given.'
            );
        }

        if (!is_string($event) && !$event instanceof EventInterface)
        {
            throw new \InvalidArgumentException(
                'The event must be a string or implement the EventInterface, "'.gettype($event).'" given.'
            );
        }

        if($event instanceof EventInterface) {
            $event = $event->getName();
        }

        foreach ($this->getListeners($event) as $priority => $listeners)
        {
            if (false !== ($key = array_search($listener, $listeners)))
            {
                unset($this->__listeners[$event][$priority][$key]);
                $this->__listeners[$event][$priority][] = $listener;
            }
        }

        return $this;
    }

    /**
     * Get the priority of an event
     *
     * @param string|EventInterface  $event     The event name or a EventInterface object
     * @param callable                $listener  The listener
     * @throws \InvalidArgumentException If the listener is not a callable
     * @throws \InvalidArgumentException  If the event is not a string or does not implement the EventInterface
     * @return integer|false The event priority or FALSE if the event isn't listened for.
     */
    public function getListenerPriority($event, $listener)
    {
        $result = false;

        if (!is_callable($listener))
        {
            throw new \InvalidArgumentException(
                'The listener must be a callable, "'.gettype($listener).'" given.'
            );
        }

        if (!is_string($event) && !$event instanceof EventInterface)
        {
            throw new \InvalidArgumentException(
                'The event must be a string or implement the EventInterface, "'.gettype($event).'" given.'
            );
        }

        if($event instanceof EventInterface) {
            $event = $event->getName();
        }

        foreach ($this->getListeners($event) as $priority => $listeners)
        {
            if (false !== ($key = array_search($listener, $listeners)))
            {
                $result = $priority;
                break;
            }
        }

        return $result;
    }

    /**
     * Enable the profiler
     *
     * @return  EventPublisherAbstract
     */
    public function setEnabled($enabled)
    {
        $this->__enabled = (bool) $enabled;
        return $this;
    }

    /**
     * Check of the publisher is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->__enabled;
    }
}
