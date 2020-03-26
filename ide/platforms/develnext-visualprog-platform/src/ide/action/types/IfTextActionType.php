<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\TextMethodsArgumentEditor;
use php\lib\str;
use php\util\Regex;

class IfTextActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'string',
            'method' => 'textMethods',
            'string' => 'string',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.text::Текст',
            'method' => 'wizard.compare.method::Метод сравнения',
            'string' => 'wizard.value::Значение',
            'not' => 'wizard.negative.logic.invert::Отрицание (все наоборот)'
        ];
    }

    function  attributeSettings()
    {
        return [
            'object' => ['def' => '~sender', 'defType' => 'object']
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
        return 'ifText';
    }

    function getTitle(Action $action = null)
    {
        if ($action) {
            $method = _(TextMethodsArgumentEditor::$variants[$action->method]);

            if ($method) {
                return _("wizard.command.param.if.text::Если текст `{0}`", $method);
            }
        }

        return 'wizard.command.if.text::Если текст ...';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.if.text::Если текст";
        }

        $method = _(TextMethodsArgumentEditor::$variants[$action->method]);

        return _("wizard.command.desc.param.if.text::Если текст {0} `{1}` -> {2} ", $action->get('object'), $method, $action->get('string'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifText16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Str::class,
            Regex::class
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
        $string = $action->get('string');

        $not = $action->not ? '!' : '';

        switch ($action->method) {
            case 'regex':
                return "if ({$not}Regex::match($string, $object))";

            case 'regexIgnoreCase':
                return "if ({$not}Regex::match($string, $object, Regex::CASE_INSENSITIVE))";

            case 'startsWith':
                return "if ({$not}str::startsWith($object, $string))";

            case 'endsWith':
                return "if ({$not}str::endsWith($object, $string))";

            case 'contains':
                return "if ({$not}str::contains($object, $string))";

            case 'equalsIgnoreCase':
                return "if ({$not}str::equalsIgnoreCase($object, $string))";

            case 'smaller':
                if ($not) {
                    return "if ($object >= $string)";
                } else {
                    return "if ($object < $string)";
                }

            case 'greater':
                if ($not) {
                    return "if ($object <= $string)";
                } else {
                    return "if ($object > $string)";
                }

            case 'equals':
            default:
                if ($action->not) {
                    return "if ($object != $string)";
                } else {
                    return "if ($object == $string)";
                }
        }
    }
}