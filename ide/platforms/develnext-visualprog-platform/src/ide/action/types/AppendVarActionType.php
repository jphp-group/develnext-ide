<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class AppendVarActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'name' => 'name',
            'value' => 'mixed',
            'asString' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'name' => 'wizard.var.name::Имя переменной',
            'value' => 'wizard.value::Значение',
            'asString' => 'wizard.as.string::Как к строке (а не к числу)'
        ];
    }

    function getSubGroup()
    {
        return 'data';
    }

    function getTagName()
    {
        return "appendVar";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.add.to.global.var::Добавить к глобальной переменной";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.add.to.global.var::Добавить к значению глобальной переменной";
        }

        $name = $action->get('name');

        if ($name[0] != '$') {
            $name = "\${$name}";
        }

        if ($action->asString) {
            return _("wizard.command.add.to.global.var.as.string::Добавить к значению переменной {0} строку {1}.", $name, $action->get('value'));
        } else {
            return _("wizard.command.add.to.global.var.as.number::Добавить к значению переменной {0} + {1}.", $name, $action->get('value'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/pointGo16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $name = $action->get('name');

        //$actionScript->addLocalVariable($name);

        if ($name[0] == '$') {
            $name = Str::sub($name, 1);
        }

        if ($action->asString) {
            return "\$GLOBALS['$name'] .= {$action->get('value')}";
        } else {
            return "\$GLOBALS['$name'] += {$action->get('value')}";
        }
    }
}