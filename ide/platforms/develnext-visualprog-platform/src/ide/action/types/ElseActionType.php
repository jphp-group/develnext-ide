<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElseActionType extends AbstractSimpleActionType
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

    function isAppendSingleLevel()
    {
        return true;
    }

    function getGroup()
    {
        return 'conditions';
    }

    function getTagName()
    {
        return 'else';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.else::Иначе ...';
    }

    function getDescription(Action $action = null)
    {
        return "wizard.command.desc.else::Иначе, т.е. если предыдущее условие не выполнилось";
    }

    function getIcon(Action $action = null)
    {
        return 'icons/else16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "else";
    }
}