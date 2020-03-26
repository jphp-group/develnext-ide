<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\io\File;
use php\lib\Str;

class IfFileExistsActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'file' => 'string',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'file' => 'wizard.path.to.file::Путь к файлу',
            'not' => 'wizard.logic.negative.not.exists::Отрицание (наоборт, если не существует)'
        ];
    }

    function isAppendSingleLevel()
    {
        return true;
    }

    function getGroup()
    {
        return 'conditions';
    }

    function getSubGroup()
    {
        return 'misc';
    }

    function getTagName()
    {
        return 'ifFileExists';
    }

    function getTitle(Action $action = null)
    {
        if (!$action || !$action->not) {
            return 'wizard.command.if.file.exists::Если есть файл ...';
        } else {
            return 'wizard.command.if.file.not.exists::Если нет файла ...';
        }
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.if.file.exists::Если существует файл";
        }

        if ($action->not) {
            return _("wizard.command.desc.param.if.file.not.exists::Если НЕ существует файл {0}.", $action->get('file'));
        } else {
            return _("wizard.command.desc.param.if.file.exists::Если существует файл {0}.", $action->get('file'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifFile16.png';
    }

    function imports(Action $action = null)
    {
        return [
            File::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $file = $action->get('file');

        if ($action->not) {
            return "if (!File::of({$file})->isFile())";
        } else {
            return "if (File::of({$file})->isFile())";
        }
    }
}