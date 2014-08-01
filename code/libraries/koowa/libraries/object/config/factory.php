<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright	Copyright (C) 2007 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		https://github.com/nooku/nooku-framework for the canonical source repository
 */

/**
 * Object Config Factory
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Config
 */
class KObjectConfigFactory extends KObject implements KObjectSingleton
{
    /**
     * Registered config file formats.
     *
     * @var array
     */
    protected $_formats;

    /**
     * Constructor
     *
     * @param KObjectConfig $config An optional KObjectConfig object with configuration options.
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_formats = $config->formats;
    }

    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config	An optional KObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'formats' => array(
                'php'  => 'KObjectConfigPhp',
                'ini'  => 'KObjectConfigIni',
                'json' => 'KObjectConfigJson',
                'xml'  => 'KObjectConfigXml',
                'yaml' => 'KObjectConfigYaml'
            )
        ));

        parent::_initialize($config);
    }

    /**
     * Get a registered config object.
     *
     * @param  string $format The format name
     * @param   array|KObjectConfig $options An associative array of configuration options or a KObjectConfig instance.
     * @throws RuntimeException            If the format isn't registered
     * @throws UnexpectedValueException	If the format object doesn't implement the KObjectConfigSerializable
     * @return KObjectConfigFormat
     */
    public function createFormat($format, $options = array())
    {
        $format = strtolower($format);

        if (!isset($this->_formats[$format])) {
            throw new RuntimeException(sprintf('Unsupported config format: %s ', $format));
        }

        $format = $this->_formats[$format];

        if(!($format instanceof KObjectConfigSerializable))
        {
            $format = new $format($options);

            if(!$format instanceof KObjectConfigSerializable)
            {
                throw new UnexpectedValueException(
                    'Format: '.get_class($format).' does not implement ObjectConfigSerializable Interface'
                );
            }

            $this->_formats[$format->name] = $format;
        }
        else $format = clone $format;

        return $format;
    }

    /**
     * Register config format
     *
     * @param string $format The name of the format
     * @param mixed  $class Class name
     * @throws InvalidArgumentException If the class does not exist
     * @return KObjectConfigFactory
     */
    public function registerFormat($format, $class)
    {
        if(!class_exists($class, true)) {
            throw new InvalidArgumentException('Class : '.$class.' cannot does not exist.');
        }

        $this->_formats[$format] = $class;
        return $this;
    }

    /**
     * Read a config from a string
     *
     * @param  string  $format
     * @param  string  $config
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @return KObjectConfigInterface
     */
    public function fromString($format, $config)
    {
        $config = $this->createFormat($format)->fromString($config);
        return $config;
    }

    /**
     * Read a config from a file.
     *
     * @param  string  $filename
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @return KObjectConfigInterface
     */
    public function fromFile($filename)
    {
        $pathinfo = pathinfo($filename);

        if (!isset($pathinfo['extension']))
        {
            throw new RuntimeException(sprintf(
                'Filename "%s" is missing an extension and cannot be auto-detected', $filename
            ));
        }

        $config = $this->createFormat($pathinfo['extension'])->fromFile($filename);
        return $config;
    }

    /**
     * Writes a config to a file
     *
     * @param string $filename
     * @param KObjectConfigInterface $config
     * @throws RuntimeException
     * @return boolean TRUE on success. FALSE on failure
     */
    public function toFile($filename, KObjectConfigInterface $config)
    {
        $pathinfo = pathinfo($filename);

        if (!isset($pathinfo['extension']))
        {
            throw new RuntimeException(sprintf(
                'Filename "%s" is missing an extension and cannot be auto-detected', $filename
            ));
        }

        return $this->createFormat($pathinfo['extension'])->toFile($filename, $config);
    }
}