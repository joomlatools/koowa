<?php
/**
 * @version 	$Id$
 * @category	Koowa
 * @package		Koowa_Factory
 * @subpackage 	Adapter
 * @copyright	Copyright (C) 2007 - 2010 Johan Janssens and Mathias Verraes. All rights reserved.
 * @license		GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 */

/**
 * Factory Adapter for a plugin
 *
 * @author		Johan Janssens <johan@koowa.org>
 * @category	Koowa
 * @package     Koowa_Factory
 * @subpackage 	Adapter
 */
class KFactoryAdapterModule extends KFactoryAdapterAbstract
{
	/**
	 * Create an instance of a class based on a class identifier
	 * 
	 * This factory will try to create an generic or default object based on the identifier information
	 * if the actual object cannot be found using a predefined fallback sequence.
	 * 
	 * Fallback sequence : -> Named Module
	 *                     -> Default Module
	 *                     -> Framework Specific
	 *                     -> Framework Default
	 *
	 * @param mixed  		 Identifier or Identifier object - application::mod.module.[.path].name
	 * @param 	object 		 An optional KConfig object with configuration options
	 * @return object|false  Return object on success, returns FALSE on failure
	 */
	public function instantiate($identifier, KConfig $config)
	{
		$instance = false;
		
		if($identifier->type == 'mod') 
		{			
			$path = KInflector::camelize(implode('_', $identifier->path));
			$classname = 'Mod'.ucfirst($identifier->package).$path.ucfirst($identifier->name);
			
			//Don't allow the auto-loader to load module classes if they don't exists yet
			if (!class_exists( $classname, false ))
			{
				//Find the file
				if($path = KLoader::load($identifier))
				{
					//Don't allow the auto-loader to load module classes if they don't exists yet
					if (!class_exists( $classname, false )) {
						throw new KFactoryAdapterException("Class [$classname] not found in file [".$path."]" );
					}
				}
				else 
				{
					$classpath = $identifier->path;
					$classtype = !empty($classpath) ? array_shift($classpath) : $identifier->name;
					
					/*
					 * Find the classname to fallback too and auto-load the class
					 * 
					 * Fallback sequence : -> Named Module
					 *                     -> Default Module
					 *                     -> Framework Specific 
					 *                     -> Framework Default
					 */
					if(class_exists('Mod'.ucfirst($identifier->package).ucfirst($identifier->name))) {
						$classname = 'Mod'.ucfirst($identifier->package).ucfirst($identifier->name);
					} elseif(class_exists('ModDefault'.ucfirst($identifier->name))) {
						$classname = 'ModDefault'.ucfirst($identifier->name);
					} elseif(class_exists( 'K'.ucfirst($classtype).$path.ucfirst($identifier->name))) {
						$classname = 'K'.ucfirst($classtype).ucfirst($identifier->name);
					} else {
						$classname = 'K'.ucfirst($classtype).'Default';
					}
				}
			}
			
			//If the object is indentifiable push the identifier in through the constructor
			if(array_key_exists('KObjectIdentifiable', class_implements($classname))) 
			{
				$identifier->filepath = KLoader::path($identifier);
				$identifier->classname = $classname;
				$config->identifier = $identifier;
			}
			
			// If the class has an instantiate method call it
			if(is_callable(array($classname, 'instantiate'), false)) {
				$instance = call_user_func(array($classname, 'instantiate'), $config);
			} else {
				$instance = new $classname($config);
			}
							
			$instance = new $classname($config);
		}

		return $instance;
	}
}