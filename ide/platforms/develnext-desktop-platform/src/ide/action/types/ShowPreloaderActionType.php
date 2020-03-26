<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\UXDialog;
use php\lib\Str;

class ShowPreloaderActionType extends AbstractSimpleActionType
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
            'value' => 'wizard.text.of.preloader::Текст загрузки'
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'showPreloader';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.show.preloader::Показать индикатор загрузки';
    }

    function getDescription(Action $action = null)
    {
        $text = $action ? $action->get('value') : "";

        if ($text >= 40) {
            $text = Str::sub($text, 0, 37) . '..';
        }

        return _("wizard.command.desc.show.preloader::Показать индикатор загрузки с сообщением {0} ", $text);
    }

    function getIcon(Action $action = null)
    {
        return 'icons/loading16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $value = $action->get('value');

        return "\$this->showPreloader({$value})";
    }
}