<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\framework\Application;

class FullScreenDisableActionType extends AbstractSimpleActionType
{
    function getTagName()
    {
        return 'fullScreenDisable';
    }

    function getGroup()
    {
        return 'system';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.disable.fullscreen::Отключить полноэкранный режим';
    }

    function getDescription(Action $action = null)
    {
        return 'wizard.command.desc.disable.fullscreen::Перевести окно в нормальный режим';
    }

    function getIcon(Action $action = null)
    {
        return 'icons/fullScreenDisable16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return '$this->getContextForm()->fullScreen = false';
    }
}