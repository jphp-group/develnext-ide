<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementAppendDataActionType extends AbstractSimpleActionType
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
            'asString' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object.of.var::Объект переменной',
            'name' => 'wizard.var.name::Имя переменной',
            'value' => 'wizard.value::Значение',
            'asString' => 'wizard.as.string::Как к строке (а не к числу)',
        ];
    }

    function attributeSettings()
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
        return "elementAppendData";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.add.to.object.data::Добавить к переменной объекта";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.add.to.object.data::Добавить значение к переменной объекта";
        }

        $name = $action->get('name');

        return _("wizard.command.desc.param.add.to.object.data::Добавить к переменной {0} объекта {1} -> значение {2}.", $name, $action->get('object'), $action->get('value'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/databaseGo16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $name = $action->get('name');

        if ($this->asString) {
            return "{$action->get('object')}->data($name, {$action->get('object')}->data($name) . {$action->get('value')})";
        } else {
            return "{$action->get('object')}->data($name, {$action->get('object')}->data($name) + {$action->get('value')})";
        }
    }
}