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
 * Dispatcher Authenticatable Behavior
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Dispatcher\Behavior
 */
class DispatcherBehaviorAuthenticatable extends DispatcherBehaviorAbstract
{
    /**
     * List of authenticators
     *
     * Associative array of authenticators, where key holds the authenticator identifier string
     * and the value is an identifier object.
     *
     * @var array
     */
    private $__authenticators;

    /**
     * Authenticator queue
     *
     * @var array
     */
    private $__authenticator_queue;

    /**
     * Constructor.
     *
     * @param ObjectConfig $config	An optional ObjectConfig object with configuration options.
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Set the filters
        $this->__authenticator_queue = $this->getObject('lib:object.queue');

        //Add the authenticators
        $authenticators = (array) ObjectConfig::unbox($config->authenticators);

        foreach ($authenticators as $key => $value)
        {
            if (is_numeric($key)) {
                $this->addAuthenticator($value);
            } else {
                $this->addAuthenticator($key, $value);
            }
        }
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param ObjectConfig $config 	An optional ObjectConfig object with configuration options.
     * @return 	void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'authenticators' => array()
        ));

        parent::_initialize($config);
    }

    /**
     * Attach an authenticator
     *
     * @param  mixed $authenticator An object that implements DispatcherAuthenticatorInterface, an ObjectIdentifier
     *                              or valid identifier string
     * @param  array  $config  An optional associative array of configuration options
     * @return $this
     */
    public function addAuthenticator($authenticator, $config = array())
    {
        //Create the complete identifier if a partial identifier was passed
        if (is_string($authenticator) && strpos($authenticator, '.') === false)
        {
            $identifier = $this->getIdentifier()->toArray();

            if($identifier['type'] == 'lib') {
                $identifier['path'] = array('authenticator');
            } else {
                $identifier['path'] = array('dispatcher', 'authenticator');
            }

            $identifier['name'] = $authenticator;

            $identifier = $this->getIdentifier($identifier);
        }
        else $identifier = $this->getIdentifier($authenticator);

        if (!isset($this->__authenticators[(string)$identifier]))
        {
            if(!$authenticator instanceof DispatcherAuthenticatorInterface) {
                $authenticator = $this->getObject($identifier, $config);
            }

            if (!($authenticator instanceof DispatcherAuthenticatorInterface))
            {
                throw new \UnexpectedValueException(
                    "Authenticator $identifier does not implement DispatcherAuthenticatorInterface"
                );
            }

            //Store the filter
            $this->__authenticators[$authenticator->getScheme()] = $authenticator;

            //Enqueue the filter
            $this->__authenticator_queue->enqueue($authenticator, $authenticator->getPriority());
        }

        return $this;
    }

    /**
     * Check if an authenticator exists
     *
     * @param 	string	$authenticator The name of the authenticator
     * @return  boolean	TRUE if the filter exists, FALSE otherwise
     */
    public function hasAuthenticator($authenticator)
    {
        return isset($this->__authenticators[$authenticator]);
    }

    /**
     * Get an authenticator by identifier
     *
     * @param   mixed $authenticator An object that implements ObjectInterface, ObjectIdentifier object
     *                               or valid identifier string
     * @return DispatcherAuthenticatorInterface|null
     */
    public function getAuthenticator($authenticator)
    {
        $result = null;

        if(isset($this->__authenticators[$authenticator])) {
            $result = $this->__authenticators[$authenticator];
        }

        return $result;
    }

    /**
     * Authenticate the request
     *
     * If an authenticator explicitly returns TRUE the authentication process will be halted and other authenticators
     * will not be called.
     *
     * @param DispatcherContextInterface $context   A dispatcher context object
     * @return void
     */
    protected function _beforeDispatch(DispatcherContext $context)
    {
        // Check if the user has been explicitly authenticated for this request
        if (!$this->getUser()->isAuthentic(true))
        {
            foreach($this->__authenticator_queue as $authenticator)
            {
                if($authenticator->authenticateRequest($context) === true) {
                    break;
                }
            }
        }

    }

    /**
     * Challenge the response
     *
     * If an authenticator explicitly returns TRUE the challenge process will be halted and other authenticators
     * will not be called.
     *
     * @param DispatcherContext $context   A dispatcher context object
     * @return void
     */
    protected function _beforeSend(DispatcherContext $context)
    {
        foreach($this->__authenticator_queue as $authenticator)
        {
            if($authenticator->challengeResponse($context) === true) {
                break;
            }
        }
    }

    /**
     * Get an object handle
     *
     * Force the object to be enqueue in the command chain.
     *
     * @return string A string that is unique, or NULL
     * @see execute()
     */
    public function getHandle()
    {
        return ObjectMixinAbstract::getHandle();
    }
}