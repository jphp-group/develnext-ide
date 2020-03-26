<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\UXDialog;
use php\lib\Str;

class HidePreloaderActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
        ];
    }

    function attributeLabels()
    {
        return [
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'hidePreloader';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.hide.preloader::Скрыть индикатор загрузки';
    }

    function getDescription(Action $action = null)
    {
        return $this->getTitle($action);
    }

    function getIcon(Action $action = null)
    {
        return 'icons/loadingRemove16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "\$this->hidePreloader()";
    }
}