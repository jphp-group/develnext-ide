<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class IfElseExprActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'expr' => 'expr',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'expr' => 'wizard.condition::Условие',
            'not' => 'wizard.logic.negative.else::Отрицание (наоборот, если не выполнится)'
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

    function getTagName()
    {
        return 'ifElseExpr';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.else.if::Иначе если ...';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.else.if::Иначе если выполнится условие";
        }

        if ($action->not) {
            return _("wizard.command.desc.param.else.if.not::Иначе если НЕ будет ({0})", $action->get('expr'));
        } else {
            return _("wizard.command.desc.param.else.if::Инече если будет ({0})", $action->get('expr'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifElse16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $expr = $action->get('expr');

        if ($action->not) {
            return "elseif (!({$expr}))";
        } else {
            return "elseif ({$expr})";
        }
    }
}