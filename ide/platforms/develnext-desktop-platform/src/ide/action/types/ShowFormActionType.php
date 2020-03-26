<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ShowFormActionType extends AbstractSimpleActionType
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
            'form' => 'wizard.name.of.form::Название формы'
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'showForm';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.show.form::Открыть форму';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.show.form::Открыть форму";
        }

        return _("wizard.command.desc.param.show.form::Открыть форму {0}.", $action->get('form'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/showForm16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $form = $action->get('form');

        return "app()->showForm({$form})";
    }
}