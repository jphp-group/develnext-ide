<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class SetVarActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'name' => 'name',
            'value' => 'mixed',
        ];
    }

    function attributeLabels()
    {
        return [
            'name' => 'wizard.name.of.variable::Имя переменной',
            'value' => 'wizard.value::Значение',
        ];
    }

    function getSubGroup()
    {
        return 'data';
    }

    function getTagName()
    {
        return "setVar";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.set.gl.var::Задать глобальную переменную";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.set.gl.var::Задать значение глобальной переменной";
        }

        $name = $action->get('name');

        if ($name[0] != '$') {
            $name = "\${$name}";
        }

        return _("wizard.command.desc.param.set.gl.var::Переменная {0} = {1} ", $name, $action->get('value'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/point16.png";
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

        return "\$GLOBALS['{$name}'] = {$action->get('value')}";
    }
}