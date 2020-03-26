<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;

/**
 * Class AppShutdownActionType
 * @package ide\action\types
 */
class AppShutdownActionType extends AbstractSimpleActionType
{
    function getTagName()
    {
        return 'applicationShutdown';
    }

    function getGroup()
    {
        return 'system';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.app.shutdown::Выход из программы';
    }

    function getDescription(Action $action = null)
    {
        return 'wizard.command.desc.app.shutdown::Полностью закрыть все окна программы и выйти из неё';
    }

    function getIcon(Action $action = null)
    {
        return 'icons/shutdown16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return 'app()->shutdown()';
    }
}