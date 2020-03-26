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

class DisplaceActionType extends AbstractSimpleActionType
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
            'x' => 'float',
            'y' => 'float',
            'continue' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'duration' => 'wizard.anim.duration::Продолжительность анимации (млсек, 1 сек = 1000 млсек)',
            'x' => 'wizard.offset.x::Смещение по X',
            'y' => 'wizard.offset.y::Смещение по Y',
            'continue' => 'wizard.no.wait.the.anim.end::Не ждать окончания анимации'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'duration' => ['def' => 1000],
            'x' => ['def' => 0],
            'y' => ['def' => 0],
        ];
    }

    function getTagName()
    {
        return "displace";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.anim.displace::Сдвинуть объект (анимация)";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.anim.displace::Анимация сдвига объекта по X и Y";
        }

        $object = $action->get('object');
        $duration = $action->get('duration');
        $x = $action->get('x');
        $y = $action->get('y');

        if ($action->continue) {
            return _("wizard.command.desc.param.anim.displace::Сдвинуть объект {0} (x: {1}, y: {2}) за {3} млсек", $object, $x, $y, $duration);
        } else {
            return _("wizard.command.desc.param.anim.displace.with.wait::Сдвинуть объект {0} (x: {1}, y: {2}) за {3} млсек с ожиданием окончания", $object, $x, $y, $duration);
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/displace16.png";
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
            return "Animation::displace({$action->get('object')}, {$action->get('duration')}, {$action->get('x')}, {$action->get('y')})";
        } else {
            return "Animation::displace({$action->get('object')}, {$action->get('duration')}, {$action->get('x')}, {$action->get('y')},";
        }
    }
}