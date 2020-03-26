<?php
namespace ide\action\types;

use action\Animation;
use ide\action\AbstractActionType;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\Str;
use php\xml\DomDocument;
use php\xml\DomElement;

class FadeToActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'ui';
    }

    function getSubGroup()
    {
        return 'anim';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'duration' => 'integer',
            'value' => 'float',
            'continue' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'duration' => 'wizard.anim.duration::Продолжительность анимации (млсек, 1 сек = 1000 млсек)',
            'value' => 'wizard.opacity.level::Уровень прозрачности (от 0 до 1)',
            'continue' => 'wizard.no.wait.the.anim.end::Не ждать окончания анимации'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'duration' => ['def' => 1000],
            'value' => ['def' => 0.5],
        ];
    }

    function getTagName()
    {
        return "fadeTo";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.anim.fadeto::Изменение прозрачности (анимация)";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.anim.fadeto::Анимация плавного изменения прозрачности объекта";
        }

        $object = $action->get('object');
        $duration = $action->get('duration');
        $value = $action->get('value');

        if ($action->continue) {
            return _("wizard.command.desc.param.anim.fadeto::Изменение прозрачности объекта {0} до {1} за {2} млсек", $object, $value, $duration);
        } else {
            return _("wizard.command.desc.param.anim.fadeto.with.wait::Изменение прозрачности объекта {0} до {1} за {2} млсек с ожиданием окончания", $object, $value, $duration);
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/fadeTo16.png";
    }

    function isYield(Action $action)
    {
        return !$action->continue;
    }

    function imports(Action $action = null)
    {
        return [
            Animation::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        if ($action->continue) {
            return "Animation::fadeTo({$action->get('object')}, {$action->get('duration')}, {$action->get('value')})";
        } else {
            return "Animation::fadeTo({$action->get('object')}, {$action->get('duration')}, {$action->get('value')},";
        }
    }
}