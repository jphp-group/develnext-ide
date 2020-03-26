<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ElementSetTextActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'value' => 'string',
            'relative' => 'flag'
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'value'  => 'wizard.text::Текст',
            'relative' => 'wizard.append.to.cur.text::Прибавить к существующему тексту'
        ];
    }

    function  attributeSettings()
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
        return 'elementSetText';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.set.element.text::Изменить текст объекта';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.set.element.text::Добавить или задать текст объекта формы";
        }

        $text = $action->get('value');

        if ($text >= 40) {
            $text = Str::sub($text, 0, 37) . '..';
        }

        if ($action->relative) {
            return _("wizard.command.desc.param.set.element.text.rel::Добавить объекту {0} текст {1}.", $action->get('object'), $text);
        } else {
            return _("wizard.command.desc.param.set.element.text::Поменять текст объекта {0} на {1}.", $action->get('object'), $text);
        }

    }

    function getIcon(Action $action = null)
    {
        return 'icons/textEdit16.png';
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
            return "Element::appendText({$object}, {$value})";
        } else {
            return "Element::setText({$object}, {$value})";
        }
    }
}