<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\UXDialog;
use php\lib\Str;

class BrowseActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'url' => 'string'
        ];
    }

    function attributeLabels()
    {
        return [
            'url' => 'wizard.url.with.http::URL (ссылка, вместе с http://)'
        ];
    }

    function getGroup()
    {
        return 'system';
    }

    function getTagName()
    {
        return 'browse';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.browse.url::Открыть URL';
    }

    function getDescription(Action $action = null)
    {
        return _("wizard.command.desc.browser.url::Открыть в браузере ссылку {0}.", $action ? $action->get('url') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/browse16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $value = $action->get('url');

        return "browse({$value})";
    }
}