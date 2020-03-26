<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\xml\DomDocument;
use php\xml\DomElement;

class GameStartAnimationActionType extends AbstractSimpleActionType
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
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.sprite.object::Объект со спрайтом'
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
        return "GameStartAnimation";
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.2d.command.start.anim::Запустить анимацию';
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.2d.command.desc.start.anim::Запустить анимацию объекта";
        }

        return _("wizard.2d.command.desc.param.start.anim::Запустить анимацию объекта {0}.", $action->get('object'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/filmStart16.png";
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "{$action->get('object')}->sprite->unfreeze()";
    }
}