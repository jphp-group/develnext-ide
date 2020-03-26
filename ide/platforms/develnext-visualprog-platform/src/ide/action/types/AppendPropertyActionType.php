<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class AppendPropertyActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'property' => 'name',
            'value' => 'mixed',
            'asString' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'property' => 'wizard.prop::Свойство',
            'value' => 'wizard.value::Значение',
            'asString' => 'wizard.as.string::Как к строке (а не к числу)',
        ];
    }

    function getSubGroup()
    {
        return 'data';
    }

    function getTagName()
    {
        return "appendProperty";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.add.to.prop::Добавить к свойству";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.add.to.prop::Добавить значение к свойству объекта";
        }

        $name = $action->get('property');

        if ($action->asString) {
            return _("wizard.command.desc.add.to.prop.as.string::Добавить к свойству {0}->{1} строку {2}.", $action->get('object'), $name, $action->get('value'));
        } else {
            return _("wizard.command.desc.add.to.prop.as.value::Добавить к свойство {0}->{1} значение {2}.", $action->get('object'), $name, $action->get('value'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/propertyGo16.png";
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

        if ($action->asString) {
            return "{$object}->{$name} .= {$action->get('value')}";
        } else {
            return "{$object}->{$name} += {$action->get('value')}";
        }
    }
}