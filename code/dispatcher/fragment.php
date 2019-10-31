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
 * Fragment Dispatcher
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Dispatcher
 */
class DispatcherFragment extends DispatcherAbstract implements ObjectInstantiable, ObjectMultiton
{
    /**
     * Constructor.
     *
     * @param ObjectConfig $config	An optional ObjectConfig object with configuration options.
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Resolve the request
        $this->addCommandCallback('before.include', '_resolveRequest');
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
            'dispatched'        => false,
            'controller'        => '',
            'controller_action' => 'render',
            'behaviors'         => array('localizable'),
        ));

        parent::_initialize($config);
    }

    /**
     * Force creation of a singleton
     *
     * @param   ObjectConfig            $config   A ObjectConfig object with configuration options
     * @param   ObjectManagerInterface  $manager  A ObjectInterface object
     * @return  DispatcherInterface
     */
    public static function getInstance(ObjectConfigInterface $config, ObjectManagerInterface $manager)
    {
        //Add the object alias to allow easy access to the singleton
        $manager->registerAlias($config->object_identifier, 'dispatcher.fragment');

        //Merge alias configuration into the identifier
        $config->append($manager->getIdentifier('dispatcher.fragment')->getConfig());

        //Instantiate the class
        $instance  = new static($config);

        return $instance;
    }

    /**
     * Get the request object
     *
     * @throws	\UnexpectedValueException	If the request doesn't implement the DispatcherRequestInterface
     * @return DispatcherRequest
     */
    public function getRequest()
    {
        if(!$this->_request instanceof DispatcherRequestInterface) {
            $this->_request = clone $this->getObject('dispatcher.request');
        }

        return $this->_request;
    }

    /**
     * Get the response object
     *
     * @throws	\UnexpectedValueException	If the response doesn't implement the DispatcherResponseInterface
     * @return DispatcherResponse
     */
    public function getResponse()
    {
        if(!$this->_response instanceof DispatcherResponseInterface) {
            $this->_response = clone $this->getObject('dispatcher.response', array(
                'request' => $this->getRequest(),
                'user'    => $this->getUser()
            ));
        }

        return $this->_response;
    }

    /**
     * Resolve the request
     *
     * @param DispatcherContext $context A dispatcher context object
     */
    protected function _resolveRequest(DispatcherContext $context)
    {
        if($controller = ObjectConfig::unbox($context->param))
        {
            $url = $this->getObject('lib:http.url', array('url' => $controller));

            //Set the request query
            $context->request->query->clear()->add($url->getQuery(true));

            //Set the controller
            $identifier = $url->toString(HttpUrl::BASE);
            $identifier = $this->getIdentifier($identifier);

            $this->setController($identifier);
        }

        parent::_resolveRequest($context);
    }

    /**
     * Include the request
     *
     * Dispatch to a controller internally or forward to another component and include the result by returning it.
     * Function makes an internal sub-request, based on the information in the request and passing along the context
     * and will return the result.
     *
     * @param DispatcherContext $context   A dispatcher context object
     * @return  mixed
     */
    protected function _actionInclude(DispatcherContext $context)
    {
        parent::_actionDispatch($context);

        return $context->result;
    }

    /**
     * Send the response
     *
     * @param DispatcherContext $context   A dispatcher context object
     * @return mixed
     */
    protected function _actionSend(DispatcherContext $context)
    {
        return $context->result;
    }
}
