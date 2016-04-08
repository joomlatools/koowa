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
 * Abstract Template Engine
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Template\Engine
 */
abstract class TemplateEngineAbstract extends TemplateAbstract implements TemplateEngineInterface
{
    /**
     * The engine file types
     *
     * @var string
     */
    protected static $_file_types = array();

    /**
     * Template object
     *
     * @var	TemplateInterface
     */
    private $__template;

    /**
     * Template stack
     *
     * Used to track recursive load calls during template evaluation
     *
     * @var array
     */
    protected $_stack;

    /**
     * Debug
     *
     * @var boolean
     */
    protected $_debug;

    /**
     * Caching enabled
     *
     * @var bool
     */
    protected $_cache;

    /**
     * Cache path
     *
     * @var string
     */
    protected $_cache_path;

    /**
     * Constructor
     *
     * @param ObjectConfig $config   An optional ObjectConfig object with configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        $this->__template = $config->template;

        //Reset the stack
        $this->_stack = array();

        //Set debug
        $this->_debug = $config->debug;

        //Set caching
        $this->_cache        = $config->cache;
        $this->_cache_path   = $config->cache_path;
        $this->_cache_reload = $config->cache_reload;
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config An optional ObjectConfig object with configuration options
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'debug'        => \Kodekit::getInstance()->isDebug(),
            'cache'        => \Kodekit::getInstance()->isCache(),
            'cache_path'   => '',
            'cache_reload' => true,
            'template'     => 'default',
            'functions'    => array(
                'object'    => array($this, 'getObject'),
                'translate' => array($this->getObject('translator'), 'translate'),
                'json'      => 'json_encode',
                'format'    => 'sprintf',
                'replace'   => 'strtr',
            ),
        ));

        parent::_initialize($config);
    }

    /**
     * Cache the template source in a file
     *
     * Write the template source to a file cache. Requires cache to be enabled. This method will throw exceptions if
     * caching fails and debug is enabled. If debug is disabled FALSE will be returned.
     *
     * @param  string $name     The file name
     * @param  string $source   The template source
     * @throws \RuntimeException If the file path does not exist
     * @throws \RuntimeException If the file path is not writable
     * @throws \RuntimeException If template cannot be written to the cache
     * @return string|false The cached file path. FALSE if the file cannot be stored in the cache
     */
    public function cache($name, $source)
    {
        if($this->_cache)
        {
            $path = $this->_cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0777, true) && !is_dir($path)))
            {
                if($this->isDebug()) {
                    throw new \RuntimeException(sprintf('The template cache path "%s" does not exist', $path));
                } else {
                    return false;
                }
            }

            if(!is_writable($path))
            {
                if($this->isDebug()) {
                    throw new \RuntimeException(sprintf('The template cache path "%s" is not writable', $path));
                } else {
                    return false;
                }
            }

            $hash = crc32($name);
            $file = $path.'/template_'.$hash.'.php';

            if(@file_put_contents($file, $source) === false)
            {
                if($this->isDebug()) {
                    throw new \RuntimeException(sprintf('The template cannot be cached in "%s"', $file));
                } else {
                    return false;
                }
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    /**
     * Get the engine supported file types
     *
     * @return array
     */
    public static function getFileTypes()
    {
        return static::$_file_types;
    }

    /**
     * Gets the template object
     *
     * @return  TemplateInterface	The template object
     */
    public function getTemplate()
    {
        if(!$this->__template instanceof TemplateInterface)
        {
            if(empty($this->__template) || (is_string($this->__template) && strpos($this->__template, '.') === false) )
            {
                $identifier         = $this->getIdentifier()->toArray();
                $identifier['path'] = array('template');
                $identifier['name'] = $this->__template;
            }
            else $identifier = $this->getIdentifier($this->__template);

            $this->__template = $this->getObject($identifier);

            if(!$this->__template instanceof TemplateInterface)
            {
                throw new \UnexpectedValueException(
                    'Template: '.get_class($this->__template).' does not implement TemplateInterface'
                );
            }
        }

        return $this->__template;
    }

    /**
     * Enable or disable debug
     *
     * @param bool $debug True or false.
     * @return TemplateEngineAbstract
     */
    public function setDebug($debug)
    {
        $this->_debug = (bool) $debug;
        return $this;
    }

    /**
     * Check if the template engine is running in debug mode
     *
     * @return bool
     */
    public function isDebug()
    {
        return $this->_debug;
    }

    /**
     * Check if a file exists in the cache
     *
     * @param string $file The file name
     * @return string|false The cache file path. FALSE if the file cannot be found in the cache
     */
    public function isCached($file)
    {
        $result = false;

        if($this->_cache)
        {
            $hash   = crc32($file);
            $cache  = $this->_cache_path.'/template_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && $this->_cache_reload && is_file($file))
            {
                if(filemtime($cache) < filemtime($file)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * Render debug information
     *
     * @param  string  $source  The template source
     * @return string The rendered template source
     */
    public function _debug($source)
    {
        //Render debug comments
        if($this->isDebug())
        {
            $template = end($this->_stack);
            $path     = str_replace(rtrim(\Kodekit::getInstance()->getRootPath(), '/').'/', '', $template['file']);
            $type     = $this->getIdentifier()->getName();

            $format  = PHP_EOL.'<!--BEGIN '.$type.':render '.$path.' -->'.PHP_EOL;
            $format .= '%s';
            $format .= PHP_EOL.'<!--END '.$type.':render '.$path.' -->'.PHP_EOL;

            $source = sprintf($format, trim($source));
        }

        return $source;
    }
}