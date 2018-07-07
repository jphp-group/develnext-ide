<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementToggleActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_COMPONENT;
    }

    function getGroup()
    {
        return self::GROUP_CONTROL;
    }

    function getTagName()
    {
        return 'elementToggle';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.toggle.visibility::Переключить видимость';
    }

    function getDescription(Action $action = null)
    {
        return _("wizard.command.desc.toggle.visibility::Переключить видимость объекта {0} с видимого на невидимый или наоборот", $action ? $action->get('object') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/eyeGo16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');

        return "{$object}->visible = !{$object}->visible";
    }
}