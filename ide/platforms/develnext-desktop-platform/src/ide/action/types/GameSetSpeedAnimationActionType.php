<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\xml\DomDocument;
use php\xml\DomElement;

class GameSetSpeedAnimationActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return 'anim';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'speed'  => 'integer',
            'relative' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.sprite.object::Объект со спрайтом',
            'speed'  => 'wizard.2d.speed::Скорость (кадров в сек)',
            'relative' => 'wizard.relative::Относительно',
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'speed'  => ['def' => 12],
        ];
    }

    function getTagName()
    {
        return "GameSetSpeedAnimation";
    }

    function getTitle(Action $action = null)
    {
        if ($action && $action->relative) {
            return "wizard.2d.command.inc.speed.anim::Увеличить скорость анимации";
        }

        return 'wizard.2d.command.set.speed.anim::Задать скорость анимации';
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.2d.command.desc.set.speed.anim::Изменить скорость анимации";
        }

        if ($action->relative) {
            return _("wizard.2d.command.desc.param.inc.speed.anim::Увеличить скорость анимации объекта {0} на {1} кадров/сек.", $action->get('object'), $action->get('speed'));
        } else {
            return _("wizard.2d.command.desc.param.set.speed.anim::Изменить скорость анимации объекта {0} на {1} кадров/сек.", $action->get('object'), $action->get('speed'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/filmNext16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        if ($action->relative) {
            return "{$action->get('object')}->sprite->speed += {$action->get('speed')}";
        } else {
            return "{$action->get('object')}->sprite->speed = {$action->get('speed')}";
        }
    }
}