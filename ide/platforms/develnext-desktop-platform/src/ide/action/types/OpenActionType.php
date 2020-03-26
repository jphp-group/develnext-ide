<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\UXDialog;
use php\lib\Str;

class OpenActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'file' => 'string'
        ];
    }

    function attributeLabels()
    {
        return [
            'file' => 'wizard.path.to.file.or.dir::Путь к файлу или папке'
        ];
    }

    function getGroup()
    {
        return 'system';
    }

    function getTagName()
    {
        return 'open';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.open.file::Открыть файл';
    }

    function getDescription(Action $action = null)
    {
        return _("wizard.command.desc.open.file::Открыть файл / папку {0} ", $action ? $action->get('file') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/openFile16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $value = $action->get('file');

        return "open({$value})";
    }
}