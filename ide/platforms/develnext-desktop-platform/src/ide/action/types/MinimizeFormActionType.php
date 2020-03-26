<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class MinimizeFormActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'form' => 'form',
            'restore' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'form' => 'wizard.form::Форма',
            'restore' => 'wizard.restore.minimized.form::Вернуть обратно свернутую форму',
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~senderForm'],
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'minimizeForm';
    }

    function getTitle(Action $action = null)
    {
        return !$action ?
            'wizard.command.minimize.form::Свернуть форму'
            : ($action->restore ? 'wizard.command.restore.minimize.form::Вернуть свернутую форму' : 'wizard.command.minimize.form::Свернуть форму');
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.minimize.form::Свернуть форму в таск бар";
        }

        if ($action->restore) {
            return _("wizard.command.desc.param.restore.minimize.form::Вернуть свернутую форму {0} из таск бара", $action->get('form'));
        } else {
            return _("wizard.command.desc.param.minimize.form::Свернуть форму {0} в таск бар", $action->get('form'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/minimizeForm16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $form = $action->get('form');

        if (!$action->restore) {
            return "app()->minimizeForm({$form})";
        } else {
            return "app()->restoreForm({$form})";
        }
    }
}