<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class ShowNewFormActionType extends AbstractSimpleActionType
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
        return 'showNewForm';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.show.new.form::Открыть новую форму';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.show.new.form::Открыть новую форму";
        }

        return _("wizard.command.param.desc.show.new.form::Открыть новую форму {0}.", $action->get('form'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/showNewForm16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $form = $action->get('form');

        return "app()->showNewForm({$form})";
    }
}