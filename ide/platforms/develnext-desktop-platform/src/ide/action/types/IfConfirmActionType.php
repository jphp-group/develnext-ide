<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class IfConfirmActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'message' => 'string',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'message' => 'wizard.question.text::Текст вопроса',
            'not' => 'wizard.logic.negation.by.user::Отрицание (наоборот, если откажется)'
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
        return 'ifConfirm';
    }

    function getTitle(Action $action = null)
    {
        if ($action && $action->not) {
            return 'wizard.command.if.not.confirm::Если откажется ...';
        } else {
            return 'wizard.command.if.confirm::Если согласится ...';
        }
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.if.confirm::Если пользователь согласится с вопросом";
        }

        if ($action->not) {
            return _("wizard.command.desc.param.if.not.confirm::Если пользователь НЕ согласится с вопросом {0}.", $action->get('message'));
        } else {
            return _("wizard.command.desc.param.if.confirm::Если пользователь согласится с вопросом {0}.", $action->get('message'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifConfirm16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $expr = $action->get('message');

        if ($action->not) {
            return "if (!uiConfirm({$expr}))";
        } else {
            return "if (uiConfirm({$expr}))";
        }
    }
}