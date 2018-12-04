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
 * Abstract Dispatcher
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Dispatcher
 */
class Dispatcher extends DispatcherAbstract implements ObjectInstantiable, ObjectMultiton
{
    /**
     * List of methods supported by the dispatcher
     *
     * @var array
     */
    protected $_methods = array();

    /**
     * Constructor.
     *
     * @param ObjectConfig $config	An optional ObjectConfig object with configuration options.
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Set the supported methods
        $this->_methods = ObjectConfig::unbox($config->methods);
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param 	ObjectConfig $config An optional ObjectConfig object with configuration options.
     * @return 	void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'methods'        => array('get', 'head', 'post', 'put', 'delete', 'options'),
            'behaviors'      => array('routable', 'limitable', 'resettable', 'localizable'),
            'authenticators' => array('csrf')
         ));

        parent::_initialize($config);
    }

    /**
     * Force creation of a singleton
     *
     * @param  ObjectConfigInterface  $config  Configuration options
     * @param  ObjectManagerInterface $manager A ObjectManagerInterface object
     * @return DispatcherInterface
     */
    public static function getInstance(ObjectConfigInterface $config, ObjectManagerInterface $manager)
    {
        //Merge alias configuration into the identifier
        $config->append($manager->getIdentifier('dispatcher')->getConfig());

        //Create the singleton
        $class    = $manager->getClass($config->object_identifier);
        $instance = new $class($config);

        //Add the object alias to allow easy access to the singleton
        $manager->registerAlias($config->object_identifier, 'dispatcher');

        return $instance;
    }

    /**
     * Resolve the request
     *
     * @param DispatcherContext $context A dispatcher context object
     * @throw DispatcherExceptionMethodNotAllowed If the HTTP request method is not allowed.
     */
    protected function _resolveRequest(DispatcherContext $context)
    {
        //Resolve the controller action
        $method = strtolower($context->request->getMethod());

        if (!in_array($method, $this->getHttpMethods())) {
            throw new DispatcherExceptionMethodNotAllowed('Method '.strtoupper($method).' not allowed');
        }

        $this->setControllerAction($method);

        parent::_resolveRequest($context);
    }

    /**
     * Dispatch the request
     *
     * Dispatch to a controller internally. Functions makes an internal sub-request, based on the information in
     * the request and passing along the context.
     *
     * @param DispatcherContext $context    A dispatcher context object
     * @return	mixed
     */
    protected function _actionDispatch(DispatcherContext $context)
    {
        $controller = $this->getController();

        if(!$controller instanceof ControllerViewable && !$controller instanceof ControllerModellable)
        {
            $action = strtolower($context->request->query->get('_action', 'alpha'));

            //Throw exception if no action could be determined from the request
            if(!$action) {
                throw new ControllerExceptionRequestInvalid('Action not found');
            }

            $controller->execute($action, $controller->getContext($context));
        }
        else $this->execute(strtolower($context->request->getMethod()), $context);

        //Set the result in the response
        if($context->result && !$context->response->isRedirect())
        {
            $result = $context->result;

            if ($result instanceof ObjectConfigFormat) {
                $context->response->setContentType($result->getMediaType());
            }

            if (is_string($result) || (is_object($result) && method_exists($result, '__toString'))) {
                $context->response->setContent($result);
            }
        }

        //Send the response
        return $this->send($context);
    }

    /**
     * Get method
     *
     * This function translates a GET request into a render action.
     *
     * @param DispatcherContext $context  A dispatcher context object
     * @return ModelEntityInterface
     */
    protected function _actionGet(DispatcherContext $context)
    {
        $controller = $this->getController();

        if($controller instanceof ControllerViewable) {
            $result = $controller->execute('render', $controller->getContext($context));
        } else {
            throw new DispatcherExceptionMethodNotAllowed('Method GET not allowed');
        }

        return $result;
    }

    /**
     * Head method
     *
     * @param DispatcherContext $context  A dispatcher context object
     * @return ModelEntityInterface
     */
    protected function _actionHead(DispatcherContext $context)
    {
        $controller = $this->getController();

        if($controller instanceof ControllerViewable) {
            $result =  $this->execute('get', $context);
        } else {
            throw new DispatcherExceptionMethodNotAllowed('Method HEAD not allowed');
        }

        return $result;
    }

    /**
     * Post method
     *
     * This function translated a POST request action into an edit or add action. If the model state is unique a edit
     * action will be executed, if not unique an add action will be executed.
     *
     * If an _action parameter exists in the request data it will be used instead. If no action can be found an bad
     * request exception will be thrown.
     *
     * @param   DispatcherContext $context  A dispatcher context object
     * @throws  DispatcherExceptionMethodNotAllowed  The action specified in the request is not allowed for the
     *          entity identified by the Request-URI. The response MUST include an Allow header containing a list of
     *          valid actions for the requested entity.
     * @throws  ControllerExceptionRequestInvalid    The action could not be found based on the info in the request.
     * @return  ModelEntityInterface
     */
    protected function _actionPost(DispatcherContext $context)
    {
        $action     = null;
        $controller = $this->getController();

        if($controller instanceof ControllerModellable)
        {
            //Get the action from the request data
            if($context->request->data->has('_action'))
            {
                $action = strtolower($context->request->data->get('_action', 'alnum'));

                if(in_array($action, array('browse', 'read', 'render', 'delete'))) {
                    throw new DispatcherExceptionMethodNotAllowed('Action: '.$action.' not allowed');
                }
            }
            else
            {
                //Determine the action based on the model state
                $action = $controller->getModel()->getState()->isUnique() ? 'edit' : 'add';
            }

            //Throw exception if no action could be determined from the request
            if(!$action) {
                throw new ControllerExceptionRequestInvalid('Action not found');
            }

            //Execute the controller action
            $result = $controller->execute($action, $controller->getContext($context));

            //Return the new representation of the resource
            if ($context->response->isSuccess())
            {
                if(!is_string($result) && !(is_object($result) && method_exists($result, '__toString'))) {
                    $result = $controller->execute('render', $controller->getContext($context));
                }
            }
        }
        else throw new DispatcherExceptionMethodNotAllowed('Method POST not allowed');

        return $result;
    }

    /**
     * Put method
     *
     * This function translates a PUT request into an edit or add action. Only if the model state is unique and the item
     * exists an edit action will be executed, if the entity does not exist and the state is unique an add action will
     * be executed.
     *
     * If the entity already exists it will be completely replaced based on the data available in the request.
     *
     * @param   DispatcherContext $context    A dispatcher context object
     * @throws  ControllerExceptionRequestInvalid  If the model state is not unique
     * @return  ModelEntityInterface
     */
    protected function _actionPut(DispatcherContext $context)
    {
        $action     = null;
        $controller = $this->getController();

        if($controller instanceof ControllerModellable)
        {
            if($controller->getModel()->getState()->isUnique())
            {
                $action = 'add';
                $entity = $controller->getModel()->fetch();

                if(!$entity->isNew())
                {
                    //Reset the row data
                    $entity->reset();
                    $action = 'edit';
                }

                //Set the row data based on the unique state information
                $state = $controller->getModel()->getState()->getValues(true);
                $entity->setProperties($state);
            }
            else throw new ControllerExceptionRequestInvalid('Resource not found');

            //Throw exception if no action could be determined from the request
            if(!$action) {
                throw new ControllerExceptionRequestInvalid('Resource not found');
            }

            //Execute the controller action
            $result = $controller->execute($action, $controller->getContext($context));

            //Return the new representation of the resource
            if ($context->response->isSuccess())
            {
                if(!is_string($result) && !(is_object($result) && method_exists($result, '__toString'))) {
                    $result = $controller->execute('render', $controller->getContext($context));
                }
            }
        }
        else throw new DispatcherExceptionMethodNotAllowed('Method PUT not allowed');

        return $result;
    }

    /**
     * Delete method
     *
     * This function translates a DELETE request into a delete action.
     *
     * @param   DispatcherContext $context A dispatcher context object
     * @throws  DispatcherExceptionMethodNotAllowed
     * @return  ModelEntityInterface
     */
    protected function _actionDelete(DispatcherContext $context)
    {
        $controller = $this->getController();

        if($controller instanceof ControllerModellable) {
            $result = $controller->execute('delete', $controller->getContext($context));
        } else {
            throw new DispatcherExceptionMethodNotAllowed('Method DELETE not allowed');
        }

        return $result;
    }

    /**
     * Options method
     *
     * @param   DispatcherContext $context    A dispatcher context object
     * @return  string  The allowed actions; e.g., `GET, POST [add, edit, cancel, save], PUT, DELETE`
     */
    protected function _actionOptions(DispatcherContext $context)
    {
        $agent   = $context->request->getAgent();
        $pattern = '#(?:Microsoft Office (?:Protocol|Core|Existence)|Microsoft-WebDAV)#i';

        if (preg_match($pattern, $agent)) {
            throw new DispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        $methods = array();

        //Retrieve HTTP methods allowed by the dispatcher
        $actions = array_intersect($this->getActions(), $this->getHttpMethods());

        foreach($actions as $action)
        {
            if($this->canExecute($action)) {
                $methods[] = strtoupper($action);
            }
        }

        $context->response->headers->set('Allow', implode(', ', $methods));
    }

    /**
     * Send the response to the client
     *
     * Add an Allow header to the response if the status code is 405 METHOD NOT ALLOWED.
     *
     * @param DispatcherContext $context   A dispatcher context object
     * @return mixed
     */
    protected function _actionSend(DispatcherContext $context)
    {
        $request  = $this->getRequest();
        $response = $this->getResponse();

        //Add an Allow header to the response
        if($response->getStatusCode() === HttpResponse::METHOD_NOT_ALLOWED)
        {
            try {
                $this->_actionOptions($context);
            }
            catch (Exception $e) {}
        }

        return parent::_actionSend($context);
    }

    /**
     * Get the supported methods
     *
     * @return array
     */
    public function getHttpMethods()
    {
        return $this->_methods;
    }
}
