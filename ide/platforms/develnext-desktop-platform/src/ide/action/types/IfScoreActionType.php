<?php
namespace ide\action\types;

use action\Element;
use action\Score;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\NumberMethodsArgumentEditor;
use ide\editors\argument\TextMethodsArgumentEditor;
use php\lib\Str;
use php\util\Regex;

class IfScoreActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'name' => 'string',
            'method' => 'numberMethods',
            'value' => 'integer',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'name' => 'wizard.score::Счет',
            'method' => 'wizard.compare.method',
            'value' => 'wizard.value.with.compare',
            'not' => 'wizard.negative.logic.invert'
        ];
    }

    function  attributeSettings()
    {
        return [
            'name' => ['def' => 'global']
        ];
    }

    function isAppendSingleLevel()
    {
        return true;
    }

    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_ADDITIONAL;
    }

    function getTagName()
    {
        return 'ifScore';
    }

    function getTitle(Action $action = null)
    {
        if ($action) {
            return _("wizard.2d.command.param.if.score::Если счет {0} ...", $action->get('name'));
        }

        return 'wizard.2d.command.if.score::Если счет ...';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.2d.command.desc.if.score::Если счет";
        }

        $method = _(NumberMethodsArgumentEditor::$variants[$action->method]);

        return _("wizard.2d.command.desc.param.if.score::Если счет {0} `{1}` -> {2} ", $action->get('name'), $method, $action->get('value'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifScore16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Score::class,
        ];
    }


    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $name = $action->get('name');

        $not = (bool) $action->not;

        $score = "Score::get($name)";
        $value = $action->get('value');

        switch ($action->method) {
            case 'equals':
                if ($not) {
                    return "if ($score != $value)";
                } else {
                    return "if ($score == $value)";
                }

            case 'smaller':
                if ($not) {
                    return "if ($score >= $value)";
                } else {
                    return "if ($score < $value)";
                }

            case 'greater':
                if ($not) {
                    return "if ($score <= $value)";
                } else {
                    return "if ($score > $value)";
                }

            case 'mod':
                if ($not) {
                    return "if ($score % $value != 0)";
                } else {
                    return "if ($score % $value == 0)";
                }
        }

        return "";
    }
}