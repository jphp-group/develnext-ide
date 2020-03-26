<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class HideFormActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'form' => 'form',
        ];
    }

    function attributeLabels()
    {
        return [
            'form' => 'wizard.form::Форма'
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
        return 'hideForm';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.close.form::Закрыть форму';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.close.form::Закрыть форму";
        }

        return _("wizard.command.desc.close.form::Закрыть форму {0}.", $action->get('form'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/hideForm16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $form = $action->get('form');

        return "app()->hideForm({$form})";
    }
}