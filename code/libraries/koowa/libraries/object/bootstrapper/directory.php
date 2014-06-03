<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */

/**
 * Directory Object Bootstrapper
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Bootstrapper
 */
class KObjectBootstrapperDirectory extends KObjectBootstrapperAbstract
{
    /**
     * The directory
     *
     * @var string
     */
    protected $_directory;

    /**
     * Constructor.
     *
     * @param KObjectConfig $config An optional ObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_directory = (array) KObjectConfig::unbox($config->directory);
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config An optional ObjectConfig object with configuration options
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'directory' => '',
        ));

        parent::_initialize($config);
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    public function bootstrap()
    {
        $chain = $this->getObject('lib:object.bootstrapper.chain');

        foreach ($this->getComponents($this->_directory) as $component)
        {
            $domain = $this->getIdentifier()->domain;
            if(!empty($domain)) {
                $identifier = 'com://'.$domain.'/'.$component.'.bootstrapper';
            } else {
                $identifier = 'com:'.$component.'.bootstrapper';
            }

            if($this->getObjectManager()->getClass($identifier))
            {
                $bootstrapper = $this->getObject($identifier);
                $chain->addBootstrapper($bootstrapper);
            }
        }

        $chain->bootstrap();
    }

    /**
     * Get the components from a directory
     *
     * @param array $directory
     * @return array
     */
    public function getComponents(array $directory)
    {
        $components = array();

        foreach($directory as $path)
        {
            foreach (new \DirectoryIterator($path) as $dir)
            {
                //Only get the component directory names
                if ($dir->isDot() || !$dir->isDir() || !preg_match('/^[a-zA-Z]+/', $dir->getBasename())) {
                    continue;
                }

                if(file_exists($dir->getPathname().'/bootstrapper.php')) {
                    $components[] = (string) $dir;
                }
            }
        }

        return array_unique($components);
    }
}