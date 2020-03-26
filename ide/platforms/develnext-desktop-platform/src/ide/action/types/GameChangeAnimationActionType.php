<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\xml\DomDocument;
use php\xml\DomElement;

class GameChangeAnimationActionType extends AbstractSimpleActionType
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
            'animation' => 'string',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.sprite.object::Объект со спрайтом',
            'animation' => 'wizard.name.of.anim::Название анимации'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function getTagName()
    {
        return "GameChangeAnimation";
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.2d.command.set.anim::Задать анимацию';
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.2d.command.desc.set.anim::Задать анимацию объекту";
        }

        return _("wizard.2d.command.desc.param.set.anim::Задать анимацию {0} объекту {1}", $action->get('animation'), $action->get('object'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/filmChange16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "{$action->get('object')}->sprite->currentAnimation = {$action->get('animation')}";
    }
}