<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetDataActionType extends AbstractSimpleActionType
{
    function isDeprecated()
    {
        return true;
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'name' => 'string',
            'value' => 'mixed',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object.of.var::Объект переменной',
            'name' => 'wizard.var.name::Имя переменной',
            'value' => 'wizard.value::Значение',
        ];
    }

    function  attributeSettings()
    {
        return [
            'object' => ['def' => '~sender']
        ];
    }

    function getGroup()
    {
        return 'logic';
    }

    function getSubGroup()
    {
        return 'data';
    }

    function getTagName()
    {
        return "elementSetData";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.set.element.data::Задать переменную объекта";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.set.element.data::Задать значение переменной объекта";
        }

        $name = $action->get('name');

        return _("wizard.command.desc.param.set.element.data::Переменная {0} объекта {1} = {2}.", $name, $action->get('object'), $action->get('value'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/database16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $name = $action->get('name');

        return "{$action->get('object')}->data($name, {$action->get('value')})";
    }
}