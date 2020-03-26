<?php
namespace ide\action\types\game;

use game\Jumping;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\ObjectArgumentEditor;
use ide\editors\common\ObjectListEditorItem;
use ide\formats\form\elements\FormFormElement;
use ide\formats\form\elements\SpriteViewFormElement;
use php\lib\str;

class SetAngleSpeedActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return 'move';
    }

    function getHelpText()
    {
        return 'wizard.2d.command.set.angle.speed.help.text::Это действие работает только для игровых объектов с поведением `Объект игровой сцены` внутри игровой комнаты или для объектов с поведением `Игровая сцена`!';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'direction' => 'float',
            'speed' => 'float',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.game.object::Игровой объект',
            'direction' => 'wizard.direction.deg.360::Направление (от 0 до 360 градусов)',
            'speed' => 'wizard.2d.speed.m.s::Скорость (м/с)'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'direction' => ['def' => 0],
            'speed' => ['def' => 0],
        ];
    }

    function getTagName()
    {
        return "setAngleSpeed";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.2d.command.set.angle.speed::Задать скорость";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            return _("wizard.2d.command.desc.param.set.angle.speed::Задать {0} объекту скорость = {1} и направление = {2}.", $action->get('object'), $action->get('speed'), $action->get('direction'));
        } else {
            return "wizard.2d.command.desc.set.angle.speed::Задать объекту скорость и направление движения";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/move16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "{$action->get('object')}->phys->angleSpeed = [{$action->get('direction')}, {$action->get('speed')}]";
    }
}