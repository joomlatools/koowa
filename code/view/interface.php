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
 * View Interface
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\View
 */
interface ViewInterface
{
    /**
     * Execute an action by triggering a method in the derived class.
     *
     * @param   array $data The view data
     * @return  string  The output of the view
     */
    public function render($data = array());

    /**
     * Set a view property
     *
     * @param   string  $property The property name.
     * @param   mixed   $value    The property value.
     * @return  ViewAbstract
     */
    public function set($property, $value);

    /**
     * Get a view property
     *
     * @param   string  $property   The property name.
     * @param  mixed  $default  Default value to return.
     * @return  string  The property value.
     */
    public function get($property, $default = null);

    /**
     * Check if a view property exists
     *
     * @param   string  $property   The property name.
     * @return  boolean TRUE if the property exists, FALSE otherwise
     */
    public function has($property);

    /**
     * Get the view data
     *
     * @return  array   The view data
     */
    public function getData();

    /**
     * Sets the view data
     *
     * @param   array $data The view data
     * @return  ViewInterface
     */
    public function setData($data);

    /**
     * Get the view parameters
     *
     * @return  array   The view parameters
     */
    public function getParameters();

    /**
     * Sets the view parameters
     *
     * @param   array $parameters The view parameters
     * @return  ViewAbstract
     */
    public function setParameters(array $parameters);

    /**
     * Get the title
     *
     * @return 	string  The title of the view
     */
    public function getTitle();

    /**
     * Set the title
     *
     * @return 	string  The title of the view
     */
    public function setTitle($title);

    /**
     * Get the content
     *
     * @return  object|string The content of the view
     */
    public function getContent();

    /**
     * Get the content
     *
     * @param  object|string $content The content of the view
     * @throws \UnexpectedValueException If the content is not a string are cannot be casted to a string.
     * @return ViewInterface
     */
    public function setContent($content);

    /**
     * Get the model object attached to the controller
     *
     * @return  ModelInterface
     */
    public function getModel();

    /**
     * Method to set a model object attached to the view
     *
     * @param   mixed   $model An object that implements ObjectInterface, ObjectIdentifier object
     *                         or valid identifier string
     * @throws  \UnexpectedValueException   If the identifier is not a table identifier
     * @return  ViewInterface
     */
    public function setModel($model);

    /**
     * Get the view url
     *
     * @return  HttpUrl  A HttpUrl object
     */
    public function getUrl();

    /**
     * Set the view url
     *
     * @param HttpUrl $url   A HttpUrl object or a string
     * @return  ViewAbstract
     */
    public function setUrl(HttpUrl $url);

    /**
     * Get the name
     *
     * @return 	string The name of the object
     */
    public function getName();

    /**
     * Get the format
     *
     * @return  string The format of the view
     */
    public function getFormat();

    /**
     * Returns the views output
     *
     * @return string
     */
    public function toString();

    /**
     * Check if we are rendering an entity collection
     *
     * @return bool
     */
    public function isCollection();
}
