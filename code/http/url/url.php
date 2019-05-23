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
 * Http Url
 *
 * This class helps you to create and manipulate urls, including query strings and path elements. It does so by splitting
 * up the pieces of the url and allowing you modify them individually; you can then then fetch them as a single url
 * string.
 *
 * The following is a simple example. Say that the page address is currently
 * `http://anonymous::guest@example.com/path/to/index.php/foo/bar?baz=dib#anchor`.
 *
 * You can use HttpUrl to parse this complex string very easily:
 *
 * <code>
 * <?php
 *     // Create a url object;
 *
 *     $url = 'http://anonymous:guest@example.com/path/to/index.php/foo/bar.xml?baz=dib#anchor'
 *     $url = Kodekit::getObject('http.url', array('url' => $url) );
 *
 *     // the $url properties are ...
 *     //
 *     // $url->scheme   => 'http'
 *     // $url->host     => 'example.com'
 *     // $url->user     => 'anonymous'
 *     // $url->pass     => 'guest'
 *     // $url->path     => array('path', 'to', 'index.php', 'foo', 'bar.xml')
 *     // $url->query    => array('baz' => 'dib')
 *     // $url->fragment => 'anchor'
 * ?>
 * </code>
 *
 * Now that we have imported the url and had it parsed automatically, we
 * can modify the component parts, then fetch a new url string.
 *
 * <code>
 * <?php
 *     // change to 'https://'
 *     $url->scheme = 'https';
 *
 *     // remove the username and password
 *     $url->user = '';
 *     $url->pass = '';
 *
 *     // change the value of 'baz' to 'zab'
 *     $url->setQuery(array('baz' => 'zab'));
 *
 *     // add a new query element called 'zim' with a value of 'gir'
 *     $url->query['zim'] = 'gir';
 *
 *     // reset the path to something else entirely.
 *     $url->setPath('/something/else/entirely.php');
 *
 *     // Get the full URL to get the scheme and host
 *     $full_url = $url->toString(true);
 *
 *     // the $full_url string is:
 *     // https://example.com/something/else/entirely.php?baz=zab&zim=gir#anchor
 * ?>
 * </code>
 *
 * @link https://tools.ietf.org/html/rfc3986
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Http\Url
 */
class HttpUrl extends ObjectAbstract implements HttpUrlInterface
{
    /**
     * The scheme [http|https|ftp|mailto|...]
     *
     * @var string
     */
    public $scheme = '';

    /**
     * The host specification (for example, 'example.com').
     *
     * @var string
     */
    public $host = '';

    /**
     * The port number (for example, '80').
     *
     * @var string
     */
    public $port = '';

    /**
     * The username, if any.
     *
     * @var string
     */
    public $user = '';

    /**
     * The password, if any.
     *
     * @var string
     */
    public $pass = '';

    /**
     * The fragment aka anchor portion (for example, the "foo" in "#foo").
     *
     * @var string
     */
    public $fragment = '';

    /**
     * The query portion (for example baz=dib)
     *
     * Public access is allowed via __get() with $query.
     *
     * @var array
     *
     * @see setQuery()
     * @see getQuery()
     */
    protected $_query = array();

    /**
     * The path portion (for example, 'path/to/index.php').
     *
     * @var array
     *
     * @see setPath()
     * @see getPath()
     */
    protected $_path = '';

    /**
     * Escapes '&' to '&amp;'
     *
     * @var boolean
     *
     * @see getQuery()
     * @see getUrl()
     */
    protected $_escape;

    /**
     * Constructor
     *
     * @param ObjectConfig $config  An optional ObjectConfig object with configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Set the escaping behavior
        $this->setEscaped($config->escape);

        //Set the url
        $this->setUrl(ObjectConfig::unbox($config->url));
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation
     *
     * @param   ObjectConfig $config  An optional ObjectConfig object with configuration options
     * @return  void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'url'    => '',
            'escape' => false
        ));

        parent::_initialize($config);
    }

    /**
     * Serialize the url
     *
     * @return string The serialised url
     */
    public function serialize()
    {
        return $this->toString();
    }

    /**
     * Unserialize the url
     *
     * @return string $url The serialised url
     */
    public function unserialize($url)
    {
        return $this->setUrl($url);
    }

    /**
     * Parse the url from a string
     *
     * Partial URLs are also accepted. setUrl() tries its best to parse them correctly. Function also accepts an
     * associative array like parse_url returns.
     *
     * @param   string|array  $url Part(s) of an URL in form of a string or associative array like parse_url() returns
     * @throws  \UnexpectedValueException If the url is not an array a string or cannot be casted to one.
     * @return  HttpUrl
     * @see     parse_url()
     */
    public function setUrl($url)
    {
        if (!is_string($url) && !is_array($url) && !(is_object($url) && method_exists($url, '__toString')))
        {
            throw new \UnexpectedValueException(
                'The url must be a array as returned by parse_url() a string or object implementing __toString(), "'.gettype($url).'" given.'
            );
        }

        if(!is_array($url)) {
            $parts = parse_url((string) $url);
        } else {
            $parts = $url;
        }

        if (is_array($parts)) {
            foreach ($parts as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Get the scheme part of the URL
     *
     * @return string|null
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the URL scheme
     *
     * @param  string $scheme
     * @return  HttpUrl
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Get the URL user
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the URL user
     *
     * @param  string $user
     * @return HttpUrl
     */
    public function setUser($user)
    {
        $this->user = rawurldecode($user);
        return $this;
    }

    /**
     * Get the URL password
     *
     * @return string|null
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Set the URL password
     *
     * @param  string $pass
     * @return HttpUrl
     */
    public function setPass($pass)
    {
        $this->pass = rawurldecode($pass);
        return $this;
    }

    /**
     * Get the URL host
     *
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the URL Host
     *
     * @param  string $host
     * @return HttpUrl
     */
    public function setHost($host)
    {
        $this->host = rawurldecode($host);
        return $this;
    }

    /**
     * Get the URL port
     *
     * @return integer|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the port part of the URL
     *
     * @param  integer $port
     * @return HttpUrl
     */
    public function setPort($port)
    {
        $this->port = (int) rawurldecode($port);
        return $this;
    }

    /**
     * Returns the path portion as a string or array
     *
     * This method will encode the path to conform to RFC 3986 returned as string.
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param   boolean $toArray If TRUE return an array. Default FALSE
     * @return  string|array The path string; e.g., `/path/to/site`.
     */
    public function getPath($toArray = false)
    {
        if(!$toArray) {
            $path = !empty($this->_path) ? '/'.implode('/', array_map('rawurlencode', $this->_path)) : '';
        }  else {
            $path = (array) $this->_path;
        }

        return $path;
    }

    /**
     * Sets the HttpUrl::$path array
     *
     * This will overwrite any previous values.
     *
     * @param   string|array  $path The path string or array of elements to use; for example,"/foo/bar/baz/dib".
     *                              A leading slash will *not* create an empty first element; if the string has a
     *                              leading slash, it is ignored.
     * @return  HttpUrl
     */
    public function setPath($path)
    {
        if (is_string($path))
        {
            if (!empty($path)) {
                $path = explode('/', ltrim($path, '/'));
            } else {
                $path = array();
            }
        }

        foreach ($path as $key => $val) {
            $path[$key] = rawurldecode($val);
        }

        $this->_path = $path;
        return $this;
    }

    /**
     * Returns the query portion as a string or array
     *
     * This method will encode the query to conform to RFC 3986 if returned as string
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param   boolean      $toArray If TRUE return an array. Default FALSE
     * @param   boolean|null $escape  If TRUE escapes '&' to '&amp;' for xml compliance. If NULL use the default.
     * @return  string|array The query string; e.g., `foo=bar&baz=dib`.
     */
    public function getQuery($toArray = false, $escape = null)
    {
        $result = $this->_query;
        $escape = isset($escape) ? (bool) $escape : $this->isEscaped();

        if(!$toArray)
        {
            $result = http_build_query($this->_query, '', $escape ? '&amp;' : '&');

            // We replace the + used for spaces by http_build_query with the more standard %20.
            $result = str_replace('+', '%20', $result);
        }

        return $result;
    }

    /**
     * Sets the query string
     *
     * If an string is provided, will decode the string to an array of parameters. Array values will be represented in
     * the query string using PHP's common square bracket notation.
     *
     * @param   string|array  $query  The query string to use; for example `foo=bar&baz=dib`.
     * @param   boolean       $merge  If TRUE the data in $query will be merged instead of replaced. Default FALSE.
     * @return  HttpUrl
     */
    public function setQuery($query, $merge = false)
    {
        //Parse
        $array = $query;
        if (!is_array($query))
        {
            if (strpos($query, '&amp;') !== false)
            {
                $query = str_replace('&amp;', '&', $query);
                $this->setEscaped(true);
            }

            //Set the query vars
            parse_str($query, $array);
        }

        //Decode
        $rawurldecode = function($array) use (&$rawurldecode)
        {
            $result = array();
            foreach($array as $key => $value)
            {
                $key = rawurldecode($key);

                if(is_array($value)) {
                    $value = $rawurldecode($value);
                } else {
                    $value = rawurldecode($value);
                }

                $result[$key] = $value;
            }

            return $result;
        };

        $result = $rawurldecode($array);

        //Merge
        if ($merge) {
            $this->_query = array_merge($this->_query, $result);
        } else {
            $this->_query = $result;
        }

        return $this;
    }

    /**
     * Get the URL fragment
     *
     * @return string|null
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Set the URL fragment part
     *
     * @param  string $fragment
     * @return HttpUrl
     */
    public function setFragment($fragment)
    {
        $this->fragment = rawurldecode($fragment);
        return $this;
    }

    /**
     * Enable/disable URL escaping
     *
     * @param bool $escape If TRUE escapes '&' to '&amp;' for xml compliance
     * @return HttpUrl
     */
    public function setEscaped($escape)
    {
        $this->_escape = (bool) $escape;
        return $this;
    }

    /**
     * Get the escape setting
     *
     * @return bool If TRUE escapes '&' to '&amp;' for xml compliance
     */
    public function isEscaped()
    {
        return $this->_escape;
    }

    /**
     * Return the url components
     *
     * @param integer $parts   A bitmask of binary or'ed HTTP_URL constants; FULL is the default
     * @param boolean|null $escape  If TRUE escapes '&' to '&amp;' for xml compliance. If NULL use the default.
     * @return array Associative array like parse_url() returns.
     * @see parse_url()
     */
    public function toArray($parts = self::FULL, $escape = null)
    {
        $result = array();
        $escape = isset($escape) ? (bool) $escape : $this->isEscaped();

        if (($parts & self::SCHEME) && !empty($this->scheme)) {
            $result['scheme'] = $this->scheme;
        }

        if (($parts & self::USER) && !empty($this->user)) {
            $result['user'] = $this->user;
        }

        if (($parts & self::PASS) && !empty($this->pass)) {
            $result['user'] = $this->pass;
        }

        if (($parts & self::PORT) && !empty($this->port)) {
            $result['port'] = $this->port;
        }

        if (($parts & self::HOST) && !empty($this->host)) {
            $result['host'] = $this->host;
        }

        if (($parts & self::PATH) && !empty($this->_path)) {
            $result['path'] = $this->_path;
        }

        if (($parts & self::QUERY) && !empty($this->_query)) {
            $result['query'] = $this->getQuery(false, $escape);
        }

        if (($parts & self::FRAGMENT) && trim($this->fragment) !== '') {
            $result['fragment'] = $this->fragment;
        }

        return $result;
    }

    /**
     * Build the url from a template
     *
     * @link http://tools.ietf.org/html/rfc6570
     *
     * @param string $template  URI template
     * @param array  $variables Template variables
     * @return HttpUrlInterface
     */
    public static function fromTemplate($template, array $variables)
    {
        if (strpos($template, '{') !== false) {
            $url = uri_template($template, $variables);
        } else {
            $url = $template;
        }

        return self::fromString($url);
    }

    /**
     * Build the url from an array
     *
     * @param   array  $parts Associative array like parse_url() returns.
     * @return  HttpUrl
     * @see     parse_url()
     */
    public static function fromArray(array $parts)
    {
        $url = new static(new ObjectConfig(array('url' => $parts)));
        return $url;
    }

    /**
     * Build the url from a string
     *
     * Partial URLs are also accepted. fromString tries its best to parse them correctly.
     *
     * @param   string  $url
     * @throws  \UnexpectedValueException If the url is not a string or cannot be casted to one.
     * @return  HttpUrl
     * @see     parse_url()
     */
    public static function fromString($url)
    {
        if (!is_string($url) && !(is_object($url) && method_exists($url, '__toString')))
        {
            throw new \UnexpectedValueException(
                'The url must be a string or object implementing __toString(), "'.gettype($url).'" given.'
            );
        }

        $url = self::fromArray(parse_url((string) $url));
        return $url;
    }

    /**
     * Get the full url, of the format scheme://user:pass@host/path?query#fragment';
     *
     * This will method will encode the resulting url to comform to RFC 3986
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param integer      $parts   A bitmask of binary or'ed HTTP_URL constants; FULL is the default
     * @param boolean|null $escape  If TRUE escapes '&' to '&amp;' for xml compliance. If NULL use the default.
     * @return  string
     */
    public function toString($parts = self::FULL, $escape = null)
    {
        $url = '';
        $escape = isset($escape) ? (bool) $escape : $this->isEscaped();

        //Add the scheme
        if (($parts & self::SCHEME) && !empty($this->scheme)) {
            $url .= rawurlencode($this->scheme) . ':';
        }

        // Add the host and port, if any.
        if (($parts & self::HOST) && !empty($this->host))
        {
            $url .= '//';

            //Add the username and password
            if (($parts & self::USER) && !empty($this->user))
            {
                $url .= rawurlencode($this->user);
                if (($parts & self::PASS) && !empty($this->pass)) {
                    $url .= ':' . rawurlencode($this->pass);
                }

                $url .= '@';
            }

            $url .= rawurlencode($this->host);

            if (($parts & self::PORT) && !empty($this->port)) {
                $url .= ':' . (int)rawurlencode($this->port);
            }
        }

        // Add the rest of the url. we use trim() instead of empty() on string
        // elements to allow for string-zero values.
        if (($parts & self::PATH) && !empty($this->_path))
        {
            $url .= $this->getPath();
        }

        if (($parts & self::QUERY) && !empty($this->_query))
        {
            if($query = $this->getQuery(false, $escape)) {
                $url .= '?' . $query;
            }
        }

        if (($parts & self::FRAGMENT) && trim($this->fragment) !== '') {
            $url .= '#' . rawurlencode($this->fragment);
        }

        return $url;
    }

    /**
     * Check if two url's are equal
     *
     * @param HttpUrlInterface $url
     * @return Boolean
     */
    public function equals(HttpUrlInterface $url)
    {
        $parts = array('scheme', 'host', 'port', 'path', 'query', 'fragment');

        foreach($parts as $part)
        {
            if($this->{$part} != $url->{$part}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the virtual properties.
     *
     * @param   string $key   The virtual property to set.
     * @param   string $value Set the virtual property to this value.
     */
    public function __set($key, $value)
    {
        if ($key == 'query') {
            $this->setQuery($value);
        }

        if ($key == 'path') {
            $this->setPath($value);
        }
    }

    /**
     * Get the virtual properties by reference so that they appears to be public
     *
     * @param   string  $key The virtual property to return.
     * @return  mixed   The value of the virtual property.
     */
    public function &__get($key)
    {
        if ($key == 'query') {
            return $this->_query;
        }

        if ($key == 'path') {
            return $this->_path;
        }

        return null;
    }

    /**
     * Allow PHP casting of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
