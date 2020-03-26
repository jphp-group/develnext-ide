<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetYActionType extends AbstractSimpleActionType
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
            'value' => 'wizard.y.position::Позиция Y',
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
        return 'elementSetY';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.set.y.position::Позиция Y';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.set.y.position::Изменить позицию Y объекта формы";
        }

        $value = $action->get('value');

        if ($action->relative) {
            return _("wizard.command.desc.param.set.y.position.rel::Добавить к позиции Y объекта {0} значение {1}.", $action->get('object'), $value);
        } else {
            return _("wizard.command.desc.param.set.y.position::Задать позицию Y объекта {0} на {1}.", $action->get('object'), $value);
        }

    }

    function getIcon(Action $action = null)
    {
        return 'icons/top16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Element::class,
        ];
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
            return "{$object}->y += {$value}";
        } else {
            return "{$object}->y = {$value}";
        }
    }
}