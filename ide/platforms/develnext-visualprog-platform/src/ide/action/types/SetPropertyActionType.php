<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class SetPropertyActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'property' => 'name',
            'value' => 'mixed',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'property' => 'wizard.property::Свойство',
            'value' => 'wizard.value::Значение',
        ];
    }

    function getSubGroup()
    {
        return 'data';
    }

    function getTagName()
    {
        return "setProperty";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.set.property::Задать свойство";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.set.property::Задать значение свойства объекта";
        }

        $name = $action->get('property');

        return _("wizard.command.desc.param.set.property::Свойство {0}->{1} = {2} ", $action->get('object'), $name, $action->get('value'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/property16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $name = $action->get('property');
        $object = $action->get('object');

        return "{$object}->{$name} = {$action->get('value')}";
    }
}