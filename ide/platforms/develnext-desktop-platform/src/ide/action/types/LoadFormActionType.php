<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;

class LoadFormActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'form' => 'form',
            'saveSize' => 'flag',
            'savePosition' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'form' => 'wizard.name.of.form::Название формы',
            'saveSize' => 'wizard.save.size::Сохранить размеры',
            'savePosition' => 'wizard.save.position::Сохранить позицию',
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'loadForm';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.load.form::Загрузить форму';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "wizard.command.desc.load.form::Загрузить форму (а текущую закрыть)";
        }

        $arg = '';

        if ($action->saveSize) {
            $arg = _('wizard.command.desc.param.load.form.save.size.param::, сохраняя размеры');
        }

        if ($action->savePosition) {
            $arg .= _('wizard.command.desc.param.load.form.save.pos.param::, сохраняя позицию');
        }

        return _("wizard.command.desc.param.load.form::Загрузить форму {0}, а текущую закрыть{1}", $action->get('form'), $arg);
    }

    function getIcon(Action $action = null)
    {
        return 'icons/loadForm16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $form = $action->get('form');

        $saveSize = $action->saveSize ? 'true' : 'false';
        $savePosition = $action->savePosition ? 'true' : 'false';

        if (!$action->saveSize && !$action->savePosition) {
            return "\$this->loadForm({$form})";
        } else {
            return "\$this->loadForm({$form}, $saveSize, $savePosition)";
        }
    }
}