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
 * Template Context
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Template\Context
 */
class TemplateContext extends Command implements TemplateContextInterface
{
    /**
     * Constructor.
     *
     * @param  array|\Traversable  $attributes An associative array or a Traversable object instance
     */
    public function __construct($attributes = array())
    {
        ObjectConfig::__construct($attributes);

        //Set the subject and the name
        if($attributes instanceof TemplateContextInterface)
        {
            $this->setSubject($attributes->getSubject());
            $this->setName($attributes->getName());
        }
    }

    /**
     * Set the view data
     *
     * @param array $data
     * @return ObjectConfigInterface
     */
    public function setData($data)
    {
        return ObjectConfig::set('data', $data);
    }

    /**
     * Get the view data
     *
     * @return array
     */
    public function getData()
    {
        return ObjectConfig::get('data');
    }

    /**
     * Set the template source
     *
     * @param string $source
     * @return ObjectConfigInterface
     */
    public function setSource($source)
    {
        return ObjectConfig::set('source', $source);
    }

    /**
     * Get the template source
     *
     * @return string
     */
    public function getSource()
    {
        return ObjectConfig::get('source');
    }

    /**
     * Set the view parameters
     *
     * @param array|ObjectConfigInterface $parameters
     * @return ObjectConfigInterface
     */
    public function setParameters($parameters)
    {
        return ObjectConfig::set('parameters', $parameters);
    }

    /**
     * Get the view parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return ObjectConfig::get('parameters');
    }
}