<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\io\File;
use php\lib\Str;

class IfPasswordValidActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'input' => 'string',
            'password' => 'string',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'input' => 'wizard.source.of.password.input::Источник ввода пароля',
            'password' => 'wizard.original.password.or.sha1::Оригинальный пароль (хеш sha1, если не строка)',
            'not' => 'wizard.logic.negative.if.password.invalid::Отрицание (если пароль неверный)',
        ];
    }

    function attributeSettings()
    {
        return [
            'input' => ['def' => '~sender', 'defType' => 'object']
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
        return 'ifPasswordValidExists';
    }

    function getHelpText()
    {
        return 'wizard.command.if.password.help.text::Для проверки паролей используется хеширование sha1 (необратимое шифрование), даже если исходники вашей программы будут открыты, оригинальный пароль никто не узнает!';
    }

    function getTitle(Action $action = null)
    {
        if (!$action || !$action->not) {
            return 'wizard.command.if.password::Если пароль верный ...';
        } else {
            return 'wizard.command.if.password.invalid::Если пароль неверный ...';
        }
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.if.password::Если пароль верный";
        }

        if ($action->not) {
            return _("wizard.command.desc.param.if.password.invalid::Если пароль из {0} неверный ({1})", $action->get('input'), $action->get('password'));
        } else {
            return _("wizard.command.desc.param.if.password::Если пароль из {0} верный ({1})", $action->get('input'), $action->get('password'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifPasswordValid16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $input = $action->get('input');

        $salt = Str::random(4);
        $password = $action->get('password');

        $not = $action->not ? '!' : '=';

        switch ($action->getFieldType('password')) {
            case 'string':
                $password = sha1($action->password . '#' . $salt);
                return "if (sha1($input . '#$salt') $not= '$password')";

            default:
                return "if (sha1($input) $not= $password)";
        }
    }
}