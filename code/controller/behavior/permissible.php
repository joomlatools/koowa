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
 * Permissible Controller Behavior
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Controller\Behavior
 */
class ControllerBehaviorPermissible extends ControllerBehaviorAbstract
{
    /**
     * The permission object
     *
     * @var ControllerPermissionInterface
     */
    protected $_permission;

    /**
     * Constructor.
     *
     * @param   ObjectConfig $config Configuration options
     */
    public function __construct(ObjectConfig $config)
    {
        parent::__construct($config);

        $this->_permission = $config->permission;
    }

    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'priority'   => self::PRIORITY_HIGH,
            'permission' => null
        ));

        parent::_initialize($config);
    }

    /**
     * Register a 'can([Execute])' function into the template
     *
     * @param CommandInterface $context
     * @return void
     */
    protected function _beforeRender(CommandInterface $context)
    {
        $controller = $context->getSubject();

        if($controller->getView() instanceof ViewTemplatable) {
            $controller->getView()->getTemplate()->registerFunction('can', array($this => 'canExecute'));
        }
    }

    /**
     * Command handler
     *
     * Only handles before.action commands to check authorization rules.
     *
     * @param CommandInterface         $command    The command
     * @param CommandChainInterface    $chain      The chain executing the command
     * @throws  ControllerExceptionRequestForbidden      If the user is authentic and the actions is not allowed.
     * @throws  ControllerExceptionRequestNotAuthorized  If the user is not authentic and the action is not allowed.
     * @return  boolean Return TRUE if action is permitted. FALSE otherwise.
     */
    public function execute(CommandInterface $command, CommandChainInterface $chain)
    {
        $parts = explode('.', $command->getName());

        if($parts[0] == 'before')
        {
            $action = $parts[1];

            $result = $this->canExecute($action);

            $invalid_statuses = array(
                HttpResponse::NOT_IMPLEMENTED,
                HttpResponse::UNAUTHORIZED,
                HttpResponse::FORBIDDEN,
                false,
            );

            if (in_array($result, $invalid_statuses))
            {
                if ($result === false && $this->getUser()->isAuthentic()) {
                    $result = HttpResponse::FORBIDDEN;
                }

                switch ($result)
                {
                    case HttpResponse::NOT_IMPLEMENTED:
                        throw new HttpExceptionNotImplemented('Action "'.ucfirst($action).'" not implemented');
                    
                    case HttpResponse::UNAUTHORIZED:
                        throw new ControllerExceptionRequestNotAuthenticated('Action "'.ucfirst($action).'" requires authentication');

                    case HttpResponse::FORBIDDEN:
                        throw new ControllerExceptionRequestForbidden('Action "'.ucfirst($action).'" not allowed');

                    default:
                        $message = 'Action "'.ucfirst($action).'" not allowed';

                        if ($this->getUser()->isAuthentic() && $this->getUser()->isEnabled()) {
                            $message .= 'User account is disabled';
                        }

                        throw new ControllerExceptionRequestForbidden($message);
                }
            }

            return true;
        }

        return true;
    }

    /**
     * Check if an action can be executed
     *
     * @param   string  $action Action name
     * @return  boolean True if the action can be executed, otherwise FALSE.
     */
    public function canExecute($action)
    {
        $method  = 'can'.ucfirst($action);
        $methods = $this->getMixer()->getMethods();

        if (!isset($methods[$method]))
        {
            $actions = $this->getActions();
            $actions = array_flip($actions);

            $result = isset($actions[$action]) ? HttpResponse::NOT_IMPLEMENTED : true;
        }
        else $result = $this->$method();

        return $result;
    }

    /**
     * Mixin Notifier
     *
     * This function is called when the mixin is being mixed. It will get the mixer passed in.
     *
     * @param ObjectMixable $mixer The mixer object
     * @return void
     */
    public function onMixin(ObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        //Create and mixin the permission if it's doesn't exist yet
        if (!$this->_permission instanceof ControllerPermissionInterface)
        {
            $permission = $this->_permission;

            if (!$permission || (is_string($permission) && strpos($permission, '.') === false))
            {
                $identifier = $mixer->getIdentifier()->toArray();
                $identifier['path'] = array('controller', 'permission');

                if ($permission) {
                    $identifier['name'] = $permission;
                }

                $permission = $this->getIdentifier($identifier);
            }

            if (!$permission instanceof ObjectIdentifierInterface) {
                $permission = $this->getIdentifier($permission);
            }

            $this->_permission = $mixer->mixin($permission);
        }
    }

    /**
     * Get an object handle
     *
     * Force the object to be enqueue in the command chain.
     *
     * @return string A string that is unique, or NULL
     * @see execute()
     */
    public function getHandle()
    {
        return ObjectMixinAbstract::getHandle();
    }
}