<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */


/**
 * Menu bar Template Helper
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Component\Koowa
 */
class ComKoowaTemplateHelperMenubar extends KTemplateHelperAbstract
{
	/**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KConfig $config Configuration options
     * @return 	void
     */
    protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'menubar' => null,
        ));

        parent::_initialize($config);
    }

 	/**
     * Render the menu bar
     *
     * @param   array   $config An optional array with configuration options
     * @return  string  Html
     */
    public function commands($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
        	'menubar' => null
        ));

        $html = '';
        if (version_compare(JVERSION, '3.0', 'ge'))
        {
        	foreach ($config->menubar->getCommands() as $command) {
                JSubmenuHelper::addEntry($this->translate($command->label), $command->href, $command->active);
        	}
        }
        else
        {
            if (count($config->menubar->getCommands()))
            {
                $html = '<div id="submenu-box"><div class="m">';

                $html .= '<ul id="submenu">';
                foreach ($config->menubar->getCommands() as $command)
                {
                    $html .= '<li>';
                    $html .= $this->command(array('command' => $command));
                    $html .= '</li>';
                }

                $html .= '</ul>';
                $html .= '<div class="clr"></div></div></div>';
            }
        }

		return $html;
    }

    /**
     * Render a menu bar command
     *
     * @param   array   $config An optional array with configuration options
     * @return  string  Html
     */
    public function command($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
        	'command' => null
        ));

        $command = $config->command;

        //Add a nolink class if the command is disabled
        if($command->disabled) {
            $command->attribs->class->append(array('nolink'));
        }

        if($command->active) {
             $command->attribs->class->append(array('active'));
        }

        //Explode the class array
        $command->attribs->class = implode(" ", KConfig::unbox($command->attribs->class));

        if ($command->disabled) {
			$html = '<span '.$this->buildAttributes($command->attribs).'>'.$this->translate($command->label).'</span>';
		} else {
			$html = '<a href="'.$command->href.'" '.$this->buildAttributes($command->attribs).'>'.$this->translate($command->label).'</a>';
		}

    	return $html;
    }
}
