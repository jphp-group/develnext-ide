<?php
namespace ide\action\types;

use action\Score;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class SetScoreActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'name' => 'string',
            'value' => 'integer',
            'relative' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'name' => 'wizard.name.of.score::Название счета',
            'value' => 'wizard.value',
            'relative' => 'wizard.relative.with.help::Относительно (т.е. прибавить к текущему значению)',
        ];
    }

    function attributeSettings()
    {
        return [
            'name' => ['def' => 'global']
        ];
    }


    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_ADDITIONAL;
    }

    function getTagName()
    {
        return "setScore";
    }

    function getTitle(Action $action = null)
    {
        if ($action && $action->relative) {
            return _("wizard.2d.command.inc.score::Прибавить к счету {0} ", $action ? $action->get('name') : '');
        } else {
            return _("wizard.2d.command.set.score::Изменить счет {0} ", $action ? $action->get('name') : '');
        }
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.2d.command.desc.set.score::Изменить счет";
        }

        $name = $action->get('name');

        if ($action->relative) {
            return _("wizard.2d.command.desc.param.inc.score::Прибавить к счету {0} -> {1}", $name, $action->get('value'));
        } else {
            return _("wizard.2d.command.desc.param.set.score::Изменит счет {0} на {1}", $name, $action->get('value'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/number16.png";
    }

    function imports(Action $action = null)
    {
        return [
            Score::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $name = $action->get('name');

        if ($action->relative) {
            return "Score::inc({$name}, {$action->get('value')})";
        } else {
            return "Score::set({$name}, {$action->get('value')})";
        }
    }
}