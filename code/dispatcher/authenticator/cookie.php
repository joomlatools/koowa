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
 * Cookie Dispatcher Authenticator
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Dispatcher\Authenticator
 */
class DispatcherAuthenticatorCookie extends DispatcherAuthenticatorOrigin
{
    /**
     * Constructor.
     *
     * @param  ObjectConfig $config Configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Setup the session cookie
        $session = $this->getObject('user')->getSession();

        //Set session cookie name
        $session->setName($config->cookie_name);

        //Set session cookie path and domain
        $session->setOptions(array(
            'use_cookies'   => 1,
            'cookie_path'   => $config->cookie_path,
            'cookie_domain' => $config->cookie_domain,
        ));
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config A ObjectConfig object with configuration options
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'cookie_name'   => md5($this->getObject('request')->getBasePath()),
            'cookie_path'   => (string) $this->getObject('request')->getBaseUrl()->getPath() ?: '/',
            'cookie_domain' => (string) $this->getObject('request')->getBaseUrl()->getHost()
        ));

        parent::_initialize($config);
    }

    /**
     * Authenticate using the cookie session id
     *
     * If a session cookie is found and the session session is not active it will be auto-started.
     *
     * @param DispatcherContext $context	A dispatcher context object
     * @return  boolean Returns TRUE if the authentication explicitly succeeded.
     */
    public function authenticateRequest(DispatcherContext $context)
    {
        $session = $context->getUser()->getSession();
        $request = $context->getRequest();

        if(!$session->isActive())
        {
            if ($request->getCookies()->has($this->getConfig()->cookie_name))
            {
                //Logging the user by auto-start the session
                $this->loginUser();

                //Perform CSRF authentication
                parent::authenticateRequest($context);

                return true;
            }
        }
    }

    /**
     * Log the user in
     *
     * @param mixed  $user A user key or name, an array of user data or a UserInterface object. Default NULL
     * @param array  $data Optional user data
     * @return bool
     */
    public function loginUser($user = null, $data = array())
    {
        if($this->_login_user)
        {
            $session  = $this->getObject('user')->getSession();
            $response = $this->getObject('response');

            //Start the session
            $session->start();

            //Set the messsages into the response
            $messages = $session->getContainer('message')->all();
            $response->setMessages($messages);

            if($user) {
                $result = parent::loginUser($user, $data);
            } else {
                $result = $this->getObject('user')->isAuthentic();
            }

            return $result;
        }

        return false;
    }
}