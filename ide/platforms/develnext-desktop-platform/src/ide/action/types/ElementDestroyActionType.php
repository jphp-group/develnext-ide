<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementDestroyActionType extends AbstractSimpleActionType
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

    function  attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function getSubGroup()
    {
        return 'object';
    }

    function getGroup()
    {
        return 'ui';
    }

    function getTagName()
    {
        return 'elementDestroy';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.destroy.object::Уничтожить объект';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.destroy.object::Уничтожить (удалить) объект формы / модуля";
        }

        return _("wizard.command.desc.param.destroy.object::Уничтожить (удалить) объект {0}.", $action->get('object'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/trash16.gif';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');

        return "{$object}->free()";
    }
}