<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class IfRandomActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'max' => 'integer',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'max' => 'wizard.max.number.cases::Максимум (количество случаев)',
            'not' => 'wizard.logic.negative.else::Отрицание (наоборт, если не выполнится)'
        ];
    }

    function  attributeSettings()
    {
        return [
            'max' => ['def' => 2]
        ];
    }


    function isAppendSingleLevel()
    {
        return true;
    }

    function getGroup()
    {
        return 'conditions';
    }

    function getSubGroup()
    {
        return 'misc';
    }

    function getTagName()
    {
        return 'ifRandom';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.if.random::Если случайность ...';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.if.random::Если выполнится случайность";
        }

        if ($action->not) {
            return _("wizard.command.desc.param.if.random.not::Если НЕ будет одного случая из {0}.", $action->get('max'));
        } else {
            return _("wizard.command.desc.param.if.random::Если будет один случай из {0}.", $action->get('max'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifRandom16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $max = $action->get('max');

        if ($action->not) {
            $expr = "rand(1, $max) != $max";
        } else {
            $expr = "rand(1, $max) == $max";
        }

        return "if ({$expr})";
    }
}