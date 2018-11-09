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
 * Abstract Controller
 *
 * Note: Concrete controllers must have a singular name
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Controller
 */
abstract class ControllerAbstract extends Object implements ControllerInterface, CommandCallbackDelegate
{
    /**
     * The actions
     *
     * @var array
     */
    protected $_actions = array();

    /**
     * Response object or identifier
     *
     * @var	string|object
     */
    protected $_response;

    /**
     * Request object or identifier
     *
     * @var	string|object
     */
    protected $_request;

    /**
     * User object or identifier
     *
     * @var	string|object
     */
    protected $_user;

    /**
     * Has the controller been dispatched
     *
     * @var boolean|DispatcherInterface
     */
    protected $_dispatched;

    /**
     * Constructor.
     *
     * @param   ObjectConfig $config Configuration options.
     */
    public function __construct( ObjectConfig $config)
    {
        parent::__construct($config);

        // Set the dispatched state
        $this->_dispatched = $config->dispatched;

        // Set the model identifier
        $this->_request = $config->request;

        // Set the view identifier
        $this->_response = $config->response;

        // Set the user identifier
        $this->_user = $config->user;

        // Mixin the behavior (and command) interface
        $this->mixin('lib:behavior.mixin', $config);
    }

    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   ObjectConfig $config Configuration options.
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'dispatched'       => false,
            'request'          => array(),
            'response'         => array(),
            'user'             => array(),
            'behaviors'        => array('permissible', 'lib:behavior.eventable'),
        ));

        parent::_initialize($config);
    }

    /**
     * Execute an action by triggering a method in the derived class.
     *
     * @param   string                      $action  The action to execute
     * @param   ControllerContext $context A command context object
     * @throws  Exception
     * @throws  \BadMethodCallException
     * @return  mixed|bool The value returned by the called method, false in error case.
     */
    public function execute($action, ControllerContext $context)
    {
        $action  = strtolower($action);

        //Retrieve the context name and subject
        $subject = $context->getSubject();
        $name    = $context->getName();

        //Execute the action
        if($this->invokeCommand('before.'.$action, $context) !== false)
        {
            $method = '_action' . ucfirst($action);

            if (!method_exists($this, $method))
            {
                if (!$this->isMixedMethod($action))
                {
                    throw new ControllerExceptionActionNotImplemented(
                        "Can't execute '$action', method: '$method' does not exist"
                    );
                }
                else $context->result = parent::__call($action, array($context));
            }
            else  $context->result = $this->$method($context);

            $this->invokeCommand('after.'.$action, $context);
        }

        //Reset the context
        $context->setSubject($subject);
        $context->setName($name);

        return $context->result;
    }

    /**
     * Invoke a command handler
     *
     * @param string             $method    The name of the method to be executed
     * @param CommandInterface  $command   The command
     * @return mixed Return the result of the handler.
     */
    public function invokeCommandCallback($method, CommandInterface $command)
    {
        return $this->$method($command);
    }

    /**
     * Mixin an object
     *
     * When using mixin(), the calling object inherits the methods of the mixed in objects, in a LIFO order.
     *
     * @@param   mixed  $mixin  An object that implements ObjectMixinInterface, ObjectIdentifier object
     *                          or valid identifier string
     * @param    array $config  An optional associative array of configuration options
     * @return  Object
     */
    public function mixin($mixin, $config = array())
    {
        if ($mixin instanceof ControllerBehaviorAbstract)
        {
            $actions = $this->getActions();

            foreach ($mixin->getMethods() as $method)
            {
                if (substr($method, 0, 7) == '_action') {
                    $actions[] = strtolower(substr($method, 7));
                }
            }

            $this->_actions = array_unique($actions);
        }

        return parent::mixin($mixin, $config);
    }

    /**
     * Gets the available actions in the controller.
     *
     * @return  array Array[i] of action names.
     */
    public function getActions()
    {
        if (!$this->_actions)
        {
            $this->_actions = array();

            foreach ($this->getMethods() as $method)
            {
                if (substr($method, 0, 7) == '_action') {
                    $this->_actions[] = strtolower(substr($method, 7));
                }
            }

            $this->_actions = array_unique($this->_actions);
        }

        return $this->_actions;
    }

    /**
     * Set the request object
     *
     * @param ControllerRequestInterface $request A request object
     * @return ControllerAbstract
     */
    public function setRequest(ControllerRequestInterface $request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Get the request object
     *
     * @throws \UnexpectedValueException	If the request doesn't implement the ControllerRequestInterface
     * @return ControllerRequestInterface
     */
    public function getRequest()
    {
        if(!$this->_request instanceof ControllerRequestInterface)
        {
            //Setup the request
            $this->_request->url  = $this->getIdentifier();
            $this->_request->user = $this->getUser();

            $this->_request = $this->getObject('lib:controller.request', ObjectConfig::unbox($this->_request));

            if(!$this->_request instanceof ControllerRequestInterface)
            {
                throw new \UnexpectedValueException(
                    'Request: '.get_class($this->_request).' does not implement ControllerRequestInterface'
                );
            }
        }

        return $this->_request;
    }

    /**
     * Set the response object
     *
     * @param ControllerResponseInterface $response A response object
     * @return ControllerAbstract
     */
    public function setResponse(ControllerResponseInterface $response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Get the response object
     *
     * @throws	\UnexpectedValueException	If the response doesn't implement the ControllerResponseInterface
     * @return ControllerResponseInterface
     */
    public function getResponse()
    {
        if(!$this->_response instanceof ControllerResponseInterface)
        {
            //Setup the response
            $this->_response->request = $this->getRequest();
            $this->_response->user    = $this->getUser();

            $this->_response = $this->getObject('lib:controller.response', ObjectConfig::unbox($this->_response));

            if(!$this->_response instanceof ControllerResponseInterface)
            {
                throw new \UnexpectedValueException(
                    'Response: '.get_class($this->_response).' does not implement ControllerResponseInterface'
                );
            }
        }

        return $this->_response;
    }

    /**
     * Set the user object
     *
     * @param UserInterface $user A request object
     * @return ControllerAbstract
     */
    public function setUser(UserInterface $user)
    {
        $this->_user = $user;
        return $this;
    }

    /**
     * Get the user object
     *
     * @throws \UnexpectedValueException	If the user doesn't implement the UserInterface
     * @return UserInterface
     */
    public function getUser()
    {
        if(!$this->_user instanceof UserInterface)
        {
            $this->_user = $this->getObject('user', ObjectConfig::unbox($this->_user));

            if(!$this->_user instanceof UserInterface)
            {
                throw new \UnexpectedValueException(
                    'User: '.get_class($this->_user).' does not implement UserInterface'
                );
            }
        }

        return $this->_user;
    }

    /**
     * Get the controller context
     *
     * @param   ControllerContextInterface $context Context to cast to a local context
     * @return  ControllerContext
     */
    public function getContext(ControllerContextInterface $context = null)
    {
        $context = new ControllerContext($context);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());

        return $context;
    }

    /**
     * Has the controller been dispatched
     *
     * @return  boolean	Returns true if the controller has been dispatched
     */
    public function isDispatched()
    {
        return $this->_dispatched;
    }

    /**
     * Execute a controller action by it's name.
     *
     * Function is also capable of checking is a behavior has been mixed successfully using is[Behavior] function. If
     * the behavior exists the function will return TRUE, otherwise FALSE.
     *
     * @param  string  $method Method name
     * @param  array   $args   Array containing all the arguments for the original call
     * @return mixed
     * @see execute()
     */
    public function __call($method, $args)
    {
        //Handle action alias method
        if(in_array($method, $this->getActions()))
        {
            //Get the data
            $data = !empty($args) ? $args[0] : array();

            //Create a context object
            if(!($data instanceof CommandInterface))
            {
                $context = $this->getContext();

                //Store the parameters in the context
                $context->param = $data;

                //Force the result to false before executing
                $context->result = false;
            }
            else $context = $data;

            //Execute the action
            return $this->execute($method, $context);
        }

        if (!$this->isMixedMethod($method))
        {
            //Check if a behavior is mixed
            $parts = StringInflector::explode($method);

            if ($parts[0] == 'is' && isset($parts[1])) {
                return false;
            }
        }

        return parent::__call($method, $args);
    }
}
