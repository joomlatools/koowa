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
 * Command Handler
 *
 * The abstract command handler will translate the command name to a method name format (eg, _before[Command] or
 * _after[Command]) and invoke the method. Command handler methods should be declared protected.
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\Command\Handler
 */
abstract class CommandHandlerAbstract extends ObjectAbstract implements CommandHandlerInterface
{
    /**
     * The command priority
     *
     * @var integer
     */
    protected $_priority;

    /**
     * Object constructor
     *
     * @param ObjectConfig $config Configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        //Set the command priority
        $this->_priority = $config->priority;
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param ObjectConfig $config An optional ObjectConfig object with configuration options
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_NORMAL,
        ));

        parent::_initialize($config);
    }

    /**
     * Execute the handler
     *
     * @param CommandInterface         $command    The command
     * @param CommandChainInterface    $chain      The chain executing the command
     * @return mixed|null If a handler breaks, returns the break condition. NULL otherwise.
     */
    public function execute(CommandInterface $command, CommandChainInterface $chain)
    {
        $parts  = explode('.', $command->getName());
        $method = '_'.$parts[0].ucfirst($parts[1]);

        if(method_exists($this, $method)) {
            return $this->$method($command);
        }
    }

    /**
     * Get the priority of the command
     *
     * @return  integer The command priority
     */
    public function getPriority()
    {
        return $this->_priority;
    }
}