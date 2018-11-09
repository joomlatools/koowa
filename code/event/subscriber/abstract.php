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
 * Event Subscriber
 *
 * An EventSusbcriber knows himself what events he is interested in. Classes extending the abstract implementation may
 * be adding listeners to an EventDispatcher through the {@link subscribe()} method.
 *
 * Listeners must be public class methods following a camel Case naming convention starting with 'on', eg onFooBar. The
 * listener priority is usually between 1 (high priority) and 5 (lowest), default is 3 (normal)
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Event\Subscriber
 */
abstract class EventSubscriberAbstract extends Object implements EventSubscriberInterface, ObjectMultiton
{
    /**
     * List of subscribed listeners
     *
     * @var array
     */
    private $__publishers;

    /**
     * Attach one or more listeners
     *
     * Event listeners always start with 'on' and need to be public methods.
     *
     * @param EventPublisherInterface $publisher
     * @param  integer                 $priority   The event priority, usually between 1 (high priority) and 5 (lowest),
     *                                 default is 3 (normal)
     * @return array An array of public methods that have been attached
     */
    public function subscribe(EventPublisherInterface $publisher, $priority = Event::PRIORITY_NORMAL)
    {
        $handle    = $publisher->getHandle();
        $listeners = [];

        if(!$this->isSubscribed($publisher))
        {
            $listeners = $this->getEventListeners();

            foreach ($listeners as $listener)
            {
                $publisher->addListener($listener, array($this, $listener), $priority);
                $this->__publishers[$handle][] = $listener;
            }
        }

        return $listeners;
    }

    /**
     * Detach all previously attached listeners for the specific dispatcher
     *
     * @param EventPublisherInterface $publisher
     * @return void
     */
    public function unsubscribe(EventPublisherInterface $publisher)
    {
        $handle = $publisher->getHandle();

        if($this->isSubscribed($publisher))
        {
            foreach ($this->__publishers[$handle] as $index => $listener)
            {
                $publisher->removeListener($listener, array($this, $listener));
                unset($this->__publishers[$handle][$index]);
            }
        }
    }

    /**
     * Check if the subscriber is already subscribed to the dispatcher
     *
     * @param  EventPublisherInterface $publisher  The event dispatcher
     * @return boolean TRUE if the subscriber is already subscribed to the dispatcher. FALSE otherwise.
     */
    public function isSubscribed(EventPublisherInterface $publisher)
    {
        $handle = $publisher->getHandle();
        return isset($this->__publishers[$handle]);
    }

    /**
     * Get the event listeners
     *
     * @return array
     */
    public static function getEventListeners()
    {
        $listeners = array();

        $reflection = new \ReflectionClass(get_called_class());
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
        {
            if(substr($method->name, 0, 2) == 'on') {
                $listeners[] = $method->name;
            }
        }

        return $listeners;
    }
}
