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
 * Http Message
 *
 * @see http://tools.ietf.org/html/rfc2616#section-4
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Http\Message
 */
abstract class HttpMessage extends Object implements HttpMessageInterface
{
    /**
     * The message headers
     *
     * @var HttpMessageHeaders
     */
    protected $_headers;

    /**
     * The http version
     *
     * @var string
     */
    protected $_version;

    /**
     * The message content
     *
     * @var string
     */
    protected $_content;

    /**
     * The message content type
     *
     * @var string
     */
    protected $_content_type;

    /**
     * Constructor
     *
     * @param ObjectConfig $config  An optional ObjectConfig object with configuration options
     * @return HttpMessage
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Set Headers
        $this->setHeaders($config->headers);

        $this->setVersion($config->version);
        $this->setContent($config->content);
        $this->setContentType($config->content_type);
    }

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
        $config->append(array(
            'version'      => '1.1',
            'content'      => '',
            'content_type' => '',
            'headers'      => array(),
        ));

        parent::_initialize($config);
    }

    /**
     * Set the header parameters
     *
     * @param  array $headers
     * @return HttpMessageInterface
     */
    public function setHeaders($headers)
    {
        $this->_headers = $this->getObject('lib:http.message.headers', array('headers' => $headers));
        return $this;
    }

    /**
     * Get the headers container
     *
     * @return HttpMessageHeaders
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Sets the HTTP protocol version (1.0 or 1.1).
     *
     * @param string $version The HTTP protocol version
     * @throws \InvalidArgumentException
     * @return HttpResponse
     */
    public function setVersion($version)
    {
        if ($version != '1.1' && $version != '1.0') {
            throw new \InvalidArgumentException('Not valid or not supported HTTP version: ' . $version);
        }

        $this->_version = $version;
        return $this;
    }

    /**
     * Gets the HTTP protocol version.
     *
     * @return string The HTTP protocol version
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Sets the response content.
     *
     * Valid types are strings, numbers, and objects that implement a __toString() method.
     *
     * @param mixed  $content   The content
     * @param string $type      The content type
     * @throws \UnexpectedValueException If the content is not a string are cannot be casted to a string.
     * @return HttpMessage
     */
    public function setContent($content, $type = null)
    {
        if (!is_null($content) && !is_string($content) && !(is_object($content) && method_exists($content, '__toString')))
        {
            throw new \UnexpectedValueException(
                'The message content must be a string or object implementing __toString(), "'.gettype($content).'" given.'
            );
        }

        //Cast to a string
        $this->_content = $content;

        if(isset($type)) {
            $this->setContentType($type);
        }

        return $this;
    }

    /**
     * Get message content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Sets the message content type
     *
     * @param string $type Content type
     * @return HttpMessage
     */
    public function setContentType($type)
    {
        $this->_content_type = $type;
        return $this;
    }

    /**
     * Retrieves the message content type
     *
     * @return string Character set
     */
    public function getContentType()
    {
        return $this->_content_type;
    }

    /**
     * Render the message as a string
     *
     * @return string
     */
    public function toString()
    {
        return $this->getContent();
    }

    /**
     * Allow PHP casting of this object
     *
     * @return string
     */
    final public function __toString()
    {
        return $this->toString();
    }

    /**
     * Deep clone of this instance
     *
     * @return void
     */
    public function __clone()
    {
        $this->_headers = clone $this->_headers;
    }
}