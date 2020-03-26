<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetValueActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'value'  => 'string',
            'relative' => 'flag'
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'value'  => 'wizard.value::Значение',
            'relative' => 'wizard.append.to.old.value::Прибавить к старому значению'
        ];
    }

    function  attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'value'  => ['editor' => 'mixed'],
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
        return 'elementSetValue';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.set.element.value::Изменить значение объекта';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.set.element.value::Добавить или задать основное значение объекта";
        }

        $text = $action->get('value');

        if ($text >= 40) {
            $text = Str::sub($text, 0, 37) . '..';
        }

        if ($action->relative) {
            return _("wizard.command.desc.param.set.element.value.rel::Добавить объекту {0} значение {1}.", $action->get('object'), $text);
        } else {
            return _("wizard.command.desc.param.set.element.value::Поменять значение объекта {0} на {1}.", $action->get('object'), $text);
        }

    }

    function getIcon(Action $action = null)
    {
        return 'icons/edit16.png';
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
            return "Element::appendValue({$object}, {$value})";
        } else {
            return "Element::setValue({$object}, {$value})";
        }
    }
}