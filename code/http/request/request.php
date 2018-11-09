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
 * Http Request
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Http\Request
 * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5
 */
class HttpRequest extends HttpMessage implements HttpRequestInterface
{
    // Methods
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const DELETE  = 'DELETE';
    const PATCH   = 'PATCH';
    const HEAD    = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const TRACE   = 'TRACE';
    const CONNECT = 'CONNECT';

    /**
     * The request method
     *
     * @var string
     */
    protected $_method;

    /**
     * URL of the request regardless of the server
     *
     * @var HttpUrl
     */
    protected $_url;

    /**
     * Constructor
     *
     * @param ObjectConfig $config  An optional ObjectConfig object with configuration options
     * @return HttpRequest
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        $this->setUrl($config->url);

        if(!empty($config->method)) {
            $this->setMethod($config->method);
        }
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
            'method'  => self::GET,
            'url'     => '',
            'headers' => array()
        ));

        parent::_initialize($config);
    }

    /**
     * Set the header parameters
     *
     * @param  array $headers
     * @return HttpRequest
     */
    public function setHeaders($headers)
    {
        $this->_headers = $this->getObject('lib:http.request.headers', array('headers' => $headers));
        return $this;
    }

    /**
     * Set the method for this request
     *
     * @param  string $method
     * @throws \InvalidArgumentException
     * @return HttpRequest
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);

        if (!defined('static::'.$method)) {
            throw new \InvalidArgumentException('Invalid HTTP method passed');
        }

        $this->_method = $method;
        return $this;
    }

    /**
     * Return the method for this request
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Set the url for this request
     *
     * @param string|array  $url Part(s) of an URL in form of a string or associative array like parse_url() returns
     * @return HttpRequest
     */
    public function setUrl($url)
    {
        $this->_url = $this->getObject('lib:http.url', array('url' => $url));
        return $this;
    }

    /**
     * Return the Url of the request regardless of the server
     *
     * @return  HttpUrl A HttpUrl object
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    public function isOptions()
    {
        return ($this->_method === self::OPTIONS);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->getMethod() === self::GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->getMethod() === self::HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->getMethod() === self::POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->getMethod() === self::PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    public function isDelete()
    {
        return ($this->getMethod() === self::DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    public function isTrace()
    {
        return ($this->getMethod() === self::TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->getMethod() === self::CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    public function isPatch()
    {
        return ($this->getMethod() === self::PATCH);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * @return boolean
     */
    public function isAjax()
    {
        $header = $this->_headers->get('X-Requested-With');
        return false !== $header && $header == 'XMLHttpRequest';
    }

    /**
     * Is this a safe request?
     *
     * @link http://tools.ietf.org/html/rfc2616#section-9.1.1
     * @return boolean
     */
    public function isSafe()
    {
        return $this->isGet() || $this->isHead() || $this->isOptions();
    }

    /**
     * Is the request cacheable
     *
     * @link https://tools.ietf.org/html/rfc7231#section-4.2.3
     * @return boolean
     */
    public function isCacheable()
    {
        return ($this->isGet() || $this->isHead()) && $this->_headers->get('Cache-Control') != 'no-cache';
    }

    /**
     * Render entire request as HTTP request string
     *
     * @return string
     */
    public function toString()
    {
        $request = sprintf('%s %s HTTP/%s', $this->getMethod(), (string) $this->getUrl(), $this->getVersion());

        $str = trim($request) . "\r\n";
        $str .= $this->getHeaders();
        $str .= "\r\n";
        $str .= $this->getContent();
        return $str;
    }

    /**
     * Deep clone of this instance
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        if($this->_url instanceof HttpUrl) {
            $this->_url = clone $this->_url;
        }
    }
}