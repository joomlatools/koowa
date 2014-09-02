<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2007 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/nooku/nooku-framework for the canonical source repository
 */

/**
 * File Override Locator
 *
 * @author  Johan Janssens <http://nooku.assembla.com/profile/johanjanssens>
 * @package Koowa\Component\Koowa\Template\Locator
 */
class ComKoowaTemplateLocatorFile extends KTemplateLocatorFile
{
    /**
     * The override path
     *
     * @var string
     */
    protected $_override_path;

    /**
     * Constructor.
     *
     * @param KObjectConfig $config  An optional KObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_override_path = $config->override_path;
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config An optional KObjectConfig object with configuration options.
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $template  = JFactory::getApplication()->getTemplate();

        $config->append(array(
            'override_path' => JPATH_THEMES.'/'.$template.'/html'
        ));

        parent::_initialize($config);
    }

    /**
     * Find a template override
     *
     * @param array  $info      The path information
     * @return bool|mixed
     */
    public function find(array $info)
    {
        if(!empty($this->_override_path))
        {
            $path   = $this->_override_path;
            $file   = pathinfo($info['url'], PATHINFO_FILENAME);
            $format = pathinfo($info['url'], PATHINFO_EXTENSION);

            if(!$result = $this->realPath($path.'/'.$file.'.'.$format))
            {
                $pattern = $path.'/'.$file.'.'.$format.'.*';

                //Try to find the file
                foreach(glob($pattern) as $file)
                {
                    if($result = $this->realPath($file)) {
                        break;
                    }
                }
            }
        }

        return parent::find($info);
    }
}