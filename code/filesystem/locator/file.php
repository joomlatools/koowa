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
 * File Filesystem Locator
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Filesystem\Locator
 */
class FilesystemLocatorFile extends FilesystemLocatorAbstract
{
    /**
     * The locator name
     *
     * @var string
     */
    protected static $_name = 'file';

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'path_templates' => array('<Package>/<Path>/<File>.<Format>')
        ));

        parent::_initialize($config);
    }
}
