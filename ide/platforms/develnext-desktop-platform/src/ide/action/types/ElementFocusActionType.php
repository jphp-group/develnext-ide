<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementFocusActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект'
        ];
    }

    function  attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function getSubGroup()
    {
        return 'object';
    }

    function getGroup()
    {
        return 'ui';
    }

    function getTagName()
    {
        return 'elementFocus';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.focus.to.object::Фокус на объект';
    }

    function getDescription(Action $action = null)
    {
        return _("wizard.command.desc.focus.to.object::Переместить фокус на объект {0}.", $action ? $action->get('object') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/focus16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');

        return "{$object}->requestFocus()";
    }
}