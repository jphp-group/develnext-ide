<?php
namespace ide\action\types\game;

use action\Collision;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;

class CollisionBounceActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_MOVING;
    }

    function getHelpText()
    {
        return 'wizard.2d.command.collision.bounce.help.text::Данное действие работает только в событии столкновения для движущихся игровых объектов';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'bounciness' => 'float',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'bounciness' => 'wizard.2d.k.of.bound::Коэффициент отскока'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'bounciness' => ['def' => 1],
        ];
    }

    function getTagName()
    {
        return "collisionBounce";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.2d.command.bounce::Отскочить";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            return _("wizard.2d.command.desc.param.bounce::Выполнить отскок для объекта {0} (во время столкновения) с коэфициентом {1} ", $action->get('object'), $action->get('bounciness'));
        } else {
            return "wizard.2d.command.desc.bounce::Выполнить отскок для объекта (во время столкновения)";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/bounce16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Collision::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $bounciness = $action->get('bounciness');

        return "Collision::bounce({$action->get('object')}, \$e->normal, $bounciness)";
    }
}