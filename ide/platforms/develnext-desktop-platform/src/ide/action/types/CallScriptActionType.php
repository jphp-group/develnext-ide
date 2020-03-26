<?php
namespace ide\action\types;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\MixedArgumentEditor;
use ide\editors\argument\ObjectArgumentEditor;
use ide\editors\common\ObjectListEditor;
use ide\editors\common\ObjectListEditorItem;
use ide\formats\form\AbstractFormElement;
use ide\formats\form\elements\FormFormElement;
use ide\scripts\elements\MacroScriptComponent;
use php\gui\UXApplication;
use php\lib\Items;
use php\lib\Str;

class CallScriptActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'script' => 'object',
            'async' => 'flag',
            'wait' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'script' => 'wizard.script.object::Скрипт-объект',
            'async' => 'wizard.call.in.background.thread::Выполнять в фоновом потоке',
            'wait' => 'wizard.wait.the.end::Ожидать окончания'
        ];
    }

    function attributeSettings()
    {
        return [
            'wait' => ['def' => true],
            'script' => [
                'editor' => function ($name, $label) {
                    $editor = new ObjectArgumentEditor([
                        'objectDisableForms' => true,

                        'objectFilter' => function (ObjectListEditorItem $item) {

                            return $item->element instanceof MacroScriptComponent
                            || $item->element instanceof FormFormElement || $item->element == null;
                        }
                    ]);
                    return $editor;
                }
            ]
        ];
    }

    function getGroup()
    {
        return 'logic';
    }

    function getTagName()
    {
        return "callScript";
    }

    function getTitle(Action $action = null)
    {
        return $action
            ? _("wizard.command.param.call.script::Выполнить `{0}`", $action->{'script-type'} == 'object' ? $action->script : $action->get('script'))
            : "wizard.command.call.script::Выполнить скрипт";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.call.script::Выполнить скрипт";
        }

        if ($action->async) {
            $result = _("wizard.command.desc.param.call.script.as.async::Выполнить скрипт {0} в фоновом потоке ", $action->get('script'));
        } else {
            $result = _("wizard.command.desc.param.call.script::Выполнить скрипт {0} ", $action->get('script'));
        }

        if ($action->wait) {
            $result .= _('wizard.command.call.script.wait.the.end::и ожидать окончания');
        }

        return $result;
    }

    function getIcon(Action $action = null)
    {
        return "icons/macro16.png";
    }

    function isYield(Action $action)
    {
        return $action->async && $action->wait;
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        if ($action->async) {
            if ($action->wait) {
                return "{$action->get('script')}->callAsync(";
            } else {
                return "{$action->get('script')}->callAsync()";
            }
        } else {
            return "{$action->get('script')}->call()";
        }
    }
}