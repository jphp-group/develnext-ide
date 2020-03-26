<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\UXDialog;
use php\lib\Str;

class ToastActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'value' => 'string'
        ];
    }

    function attributeLabels()
    {
        return [
            'value' => 'wizard.text.of.message::Текст сообщения'
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'toast';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.toast::Всплывающая подсказка';
    }

    function getDescription(Action $action = null)
    {
        $text = $action ? $action->get('value') : "";

        if ($text >= 40) {
            $text = Str::sub($text, 0, 37) . '..';
        }

        return _("wizard.command.desc.toast::Показать всплывающую подсказку {0} ", $text);
    }

    function getIcon(Action $action = null)
    {
        return 'icons/tooltip16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $value = $action->get('value');

        return "\$this->toast({$value})";
    }
}