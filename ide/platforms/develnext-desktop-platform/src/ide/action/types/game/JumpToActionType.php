<?php
namespace ide\action\types\game;

use game\Jumping;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\str;

class JumpToActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return 'move';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'x' => 'integer',
            'y' => 'integer',
            'relative' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object',
            'x' => 'wizard.x.position',
            'y' => 'wizard.y.position',
            'relative' => 'wizard.relative'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'x' => ['def' => '0'],
            'y' => ['def' => '0'],
        ];
    }

    function getTagName()
    {
        return "jumpingTo";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.2d.command.jump.to.pos::Прыгнуть в позицию";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            if ($action->relative) {
                return _("wizard.2d.command.desc.param.jump.to.pos.rel::Переместить объект {0} к относительной позиции (x: {1}, y: {2})", $action->get('object'), $action->get('x'), $action->get('y'));
            } else {
                return _("wizard.2d.command.desc.param.jump.to.pos::Переместить объект {0} к позиции (x: {1}, y: {2})", $action->get('object'), $action->get('x'), $action->get('y'));
            }
        } else {
            return "wizard.2d.command.desc.jump.to.pos::Переместить объект к позиции (x, y)";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/jump16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Jumping::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        if ($action->relative) {
            return "Jumping::to({$action->get('object')}, {$action->get('x')}, {$action->get('y')}, true)";
        } else {
            return "Jumping::to({$action->get('object')}, {$action->get('x')}, {$action->get('y')})";
        }
    }
}