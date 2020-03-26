<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetHeightActionType extends AbstractSimpleActionType
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
            'value' => 'wizard.height::Высота',
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
        return 'elementSetHeight';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.set.height::Изменить высоту';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.set.height::Изменить высоту объекта формы";
        }

        $value = $action->get('value');

        if ($action->relative) {
            return _("wizard.command.desc.param.set.height.relative::Увеличить высоту объекта {0} на {1}.", $action->get('object'), $value);
        } else {
            return _("wizard.command.desc.param.set.height::Задать высоту объекта {0} на {1}.", $action->get('object'), $value);
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/height16.png';
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
            return "{$object}->height += {$value}";
        } else {
            return "{$object}->height = {$value}";
        }
    }
}