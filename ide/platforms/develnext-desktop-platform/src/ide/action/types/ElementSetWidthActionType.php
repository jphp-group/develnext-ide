<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetWidthActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'value'  => 'integer',
            'relative' => 'flag'
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'value' => 'wizard.width::Ширина',
            'relative' => 'wizard.relative::Относительно'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function getGroup()
    {
        return 'ui';
    }

    function getSubGroup()
    {
        return 'object';
    }

    function getTagName()
    {
        return 'elementSetWidth';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.set.width::Изменить ширину';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.set.width::Изменить ширину объекта формы";
        }

        $value = $action->get('value');

        if ($action->relative) {
            return _("wizard.command.desc.param.set.width.rel::Увеличить ширину объекта {0} на {1}.", $action->get('object'), $value);
        } else {
            return _("wizard.command.desc.param.set.width::Задать ширину объекта {0} на {1}.", $action->get('object'), $value);
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/width16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $object = $action->get('object');
        $value = $action->get('value');

        if ($action->relative) {
            return "{$object}->width += {$value}";
        } else {
            return "{$object}->width = {$value}";
        }
    }
}