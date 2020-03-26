<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetXActionType extends AbstractSimpleActionType
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
            'value' => 'wizard.x.position::Позиция X',
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
        return 'elementSetX';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.set.x.position::Позиция X';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.set.x.position::Изменить позицию X объекта формы";
        }

        $value = $action->get('value');

        if ($action->relative) {
            return _("wizard.command.desc.param.set.x.position.rel::Добавить к позиции X объекта {0} значение {1}.", $action->get('object'), $value);
        } else {
            return _("wizard.command.desc.param.set.x.position::Задать позицию X объекта {0} на {1}.", $action->get('object'), $value);
        }

    }

    function getIcon(Action $action = null)
    {
        return 'icons/right16.png';
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
            return "{$object}->x += {$value}";
        } else {
            return "{$object}->x = {$value}";
        }
    }
}