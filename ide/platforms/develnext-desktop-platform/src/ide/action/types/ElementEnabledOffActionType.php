<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementEnabledOffActionType extends AbstractSimpleActionType
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
        return 'elementEnabledOff';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.disable.element::Сделать недоступным';
    }

    function getDescription(Action $action = null)
    {
        return _("wizard.command.desc.disable.element::Сделать объект {0} недоступным.", $action ? $action->get('object') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/enabledOff16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');

        return "{$object}->enabled = false";
    }
}