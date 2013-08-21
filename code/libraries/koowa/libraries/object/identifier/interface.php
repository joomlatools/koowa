<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */

/**
 * Object Identifier Interface
 *
 * Wraps identifiers of the form type:[//application/]package.[.path].name in an object, providing public accessors and
 * methods for derived formats.
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Object
 */
interface KObjectIdentifierInterface extends Serializable
{
    /**
     * Set an application path
     *
     * @param string $application The name of the application
     * @param string $path        The path of the application
     * @return void
     */
    public static function registerApplication($application, $path);

    /**
     * Get an application path
     *
     * @param string    $application   The name of the application
     * @return string	The path of the application
     */
    public static function getApplication($application);

    /**
     * Get a list of applications
     *
     * @return array
     */
    public static function getApplications();

    /**
     * Set a package path
     *
     * @param string $package    The name of the package
     * @param string $path       The path of the package
     * @return void
     */
    public static function registerPackage($package, $path);

    /**
     * Get a package path
     *
     * @param string    $package   The name of the application
     * @return string	The path of the application
     */
    public static function getPackage($package);

    /**
     * Get a list of packages
     *
     * @return array
     */
    public static function getPackages();

    /**
     * Add a object locator
     *
     * @param KObjectLocatorInterface $locator
     * @return KObjectIdentifierInterface
     */
    public static function addLocator(KObjectLocatorInterface $locator);

    /**
     * Get the object locator
     *
     * @return KObjectLocatorInterface|null  Returns the object locator or NULL if the locator can not be found.
     */
    public function getLocator();

    /**
     * Get the decorators
     *
     *  @return array
     */
    public static function getLocators();

    /**
     * Formats the identifier as a [application::]type.component.[.path].name string
     *
     * @return string
     */
    public function toString();
}