<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementEnabledOnActionType extends AbstractSimpleActionType
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

    function attributeSettings()
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
        return 'elementEnabledOn';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.enable.element::Сделать доступным';
    }

    function getDescription(Action $action = null)
    {
        return Str::format("wizard.command.desc.enable.element::Сделать объект {0} доступным.", $action ? $action->get('object') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/enabledOn16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');

        return "{$object}->enabled = true";
    }
}