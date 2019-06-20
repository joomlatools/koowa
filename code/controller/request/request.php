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
 * Controller Request
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Controller\Request
 */
class ControllerRequest extends HttpRequest implements ControllerRequestInterface
{
    /**
     * The request query
     *
     * @var HttpMessageParameters
     */
    protected $_query;

    /**
     * The request data
     *
     * @var HttpMessageParameters
     */
    protected $_data;

    /**
     * User object
     *
     * @var	string|object
     */
    protected $_user;

    /**
     * Constructor
     *
     * @param ObjectConfig|null $config  An optional ObjectConfig object with configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        // Set the user identifier
        $this->_user = $config->user;

        //Set query parameters
        $this->setQuery($config->query);

        //Set data parameters
        $this->setData($config->data);
    }

    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'query'    => array(),
            'data'     => array(),
            'format'   => 'html',
            'user'     => array(),
            'language' => locale_get_default(),
            'timezone' => date_default_timezone_get(),
        ));

        parent::_initialize($config);
    }

    /**
     * Return the request format or mediatype
     *
     * Find the format by using following sequence :
     *
     * 1. Use the the 'format' request parameter
     * 2. Use the URL path extension
     * 3. Use the accept header with the highest quality apply the reverse format map to find the format.
     *
     * @param   bool    $mediatype Get the media type
     * @return  string  The request format or NULL if no format could be found
     */
    public function getFormat($mediatype = false)
    {
        if (!isset($this->_format))
        {
            if(!$this->query->has('format')) {
                $format = parent::getFormat() ?: $this->getConfig()->format;
            } else {
                $format = $this->query->get('format', 'word');
            }

            $this->_format = $format;
        }

        return $mediatype ? static::$_formats[$this->_format][0] : $this->_format;
    }

    /**
     * Return the Url of the request regardless of the server
     *
     * @return  HttpUrl A HttpUrl object
     */
    public function getUrl()
    {
        $url = parent::getUrl();

        //Add the query to the URL
        $url->setQuery($this->getQuery()->toArray());

        return $url;
    }

    /**
     * Set the request query
     *
     * @param  array $parameters
     * @return ControllerRequest
     */
    public function setQuery($parameters)
    {
        $this->_query = $this->getObject('lib:http.message.parameters', array('parameters' => $parameters));
        return $this;
    }

    /**
     * Get the request query
     *
     * @return HttpMessageParameters
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Set the request data
     *
     * @param  array $parameters
     * @return ControllerRequest
     */
    public function setData($parameters)
    {
        $this->_data = $this->getObject('lib:http.message.parameters', array('parameters' => $parameters));
        return $this;
    }

    /**
     * Get the request query
     *
     * @return HttpMessageParameters
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Set the user object
     *
     * @param UserInterface $user A request object
     * @return ControllerRequest
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
     * Returns the request language tag
     *
     * Should return a properly formatted IETF language tag, eg xx-XX
     * @link https://en.wikipedia.org/wiki/IETF_language_tag
     * @link https://tools.ietf.org/html/rfc5646
     *
     * @return string
     */
    public function getLanguage()
    {
        if(!$language = $this->getUser()->getLanguage()) {
            $language = $this->getConfig()->language;
        }

        return $language;
    }

    /**
     * Returns the request timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        if(!$timezone = $this->getUser()->getLanguage()) {
            $timezone = $this->getConfig()->timezone;
        }

        return $timezone;
    }

    /**
     * Implement a virtual 'headers', 'query' and 'data class property to return their respective objects.
     *
     * @param   string $name  The property name.
     * @return  mixed The property value.
     */
    public function __get($name)
    {
        $result = null;
        if($name == 'headers') {
            $result = $this->getHeaders();
        }

        if($name == 'query') {
            $result = $this->getQuery();
        }

        if($name == 'data') {
            $result =  $this->getData();
        }

        return $result;
    }

    /**
     * Deep clone of this instance
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->_data  = clone $this->_data;
        $this->_query = clone $this->_query;
        $this->_user  = clone $this->_user;
    }
}