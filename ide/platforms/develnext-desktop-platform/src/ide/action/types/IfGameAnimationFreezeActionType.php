<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\io\File;
use php\lib\Str;

class IfGameAnimationFreezeActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'not' => 'wizard.logic.negative.not.anim::Отрицание (наоборот, не анимирован)'
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
        return 'ifGameAnimationFreeze';
    }

    function getTitle(Action $action = null)
    {
        if ($action && $action->not) {
            return "wizard.2d.command.if.object.not.anim::Если объект НЕ анимирован";
        }

        return 'wizard.2d.command.if.object.anim::Если объект анимирован ...';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.2d.command.desc.if.object.anim::Если объект анимирован";
        }

        if ($action->not) {
            return _("wizard.2d.command.desc.param.if.object.not.anim::Если объект {0} НЕ анимирован", $action->get('object'));
        } else {
            return _("wizard.2d.command.desc.param.if.object.anim::Если объект {0} анимирован", $action->get('object'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifGameAnimationFreeze16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');

        $op = "!";

        if ($action->not) {
            $op = "";
        }

        return "if ({$op}{$object}->sprite->isFreeze())";
    }
}