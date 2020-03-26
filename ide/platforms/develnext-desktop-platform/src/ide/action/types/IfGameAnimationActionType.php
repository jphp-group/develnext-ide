<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\io\File;
use php\lib\Str;

class IfGameAnimationActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'animation' => 'string',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'animation' => 'wizard.2d.anim::Анимация',
            'not' => 'wizard.logic.negative.not.played::Отрицание (наоборт, если не проигрывается)'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function isAppendSingleLevel()
    {
        return true;
    }

    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return 'anim';
    }

    function getTagName()
    {
        return 'ifGameAnimation';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.2d.command.if.anim::Если анимация ...';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.2d.command.desc.if.anim::Если проигрывается анимация";
        }

        if ($action->not) {
            return _("wizard.2d.command.desc.param.if.not.anim::Если НЕ проигрывается анимация {0} у объекта {1}.", $action->get('animation'), $action->get('object'));
        } else {
            return _("wizard.2d.command.desc.param.if.anim::Если проигрывается анимация {0} у объекта {1}", $action->get('animation'), $action->get('object'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifGameAnimation16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');
        $animation = $action->get('animation');

        $op = "==";

        if ($action->not) {
            $op = "!=";
        }

        return "if ({$object}->sprite->currentAnimation {$op} {$animation})";
    }
}