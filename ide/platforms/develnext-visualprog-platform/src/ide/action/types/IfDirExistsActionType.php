<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\io\File;
use php\lib\Str;

class IfDirExistsActionType extends AbstractSimpleActionType
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
            'file' => 'wizard.path.to.dir::Путь к папке',
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
        return 'ifDirExists';
    }

    function getTitle(Action $action = null)
    {
        if (!$action || !$action->not) {
            return 'wizard.command.if.dir.exists::Если есть папка ...';
        } else {
            return 'wizard.command.if.dir.not.exists::Если нет папки ...';
        }
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.if.dir.exists::Если существует папка";
        }

        if ($action->not) {
            return _("wizard.command.desc.param.if.dir.not.exists::Если НЕ существует папка {0}.", $action->get('file'));
        } else {
            return _("wizard.command.desc.param.if.dir.exists::Если существует папка {0}.", $action->get('file'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifDir16.png';
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
            return "if (!File::of({$file})->isDirectory())";
        } else {
            return "if (File::of({$file})->isDirectory())";
        }
    }
}