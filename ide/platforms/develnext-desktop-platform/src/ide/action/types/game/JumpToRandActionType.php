<?php
namespace ide\action\types\game;

use game\Jumping;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\str;

class JumpToRandActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return 'move';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'gridX' => 'integer',
            'gridY' => 'integer',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object',
            'gridX' => 'wizard.2d.grid.x.hor::Grid X (горизонтальное выравнивание)',
            'gridY' => 'wizard.2d.grid.y.ver::Grid Y (вертикальное выравнивание)',
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'gridX' => ['def' => '1'],
            'gridY' => ['def' => '1'],
        ];
    }

    function getTagName()
    {
        return "jumpingToRand";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.2d.command.jump.to.rand.place::Прыгнуть в случайное место";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            $gridX = $action->get('gridX');
            $gridY = $action->get('gridY');

            if ($gridX <= 1 && $gridY <= 1) {
                return _("wizard.2d.command.desc.param.jump.to.rand.place::Переместить {0} объект к случайно выбранной позиции", $action->get('object'));
            } else {
                return _("wizard.2d.command.desc.param.jump.to.rand.place.grid::Переместить {0} объект к случайно выбранной позиции, с выравниванием (x: {1}, y: {2})", $action->get('object'), $gridX, $gridY);
            }
        } else {
            return "wizard.2d.command.desc.jump.to.rand.place::Переместить объект к случайно выбранной позиции";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/jumpToRand16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Jumping::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $gridX = $action->get('gridX');
        $gridY = $action->get('gridY');
        $object = $action->get('object');

        if ($gridX <= 1 && $gridY <= 1) {
            return "Jumping::toRand({$object})";
        } else {
            return "Jumping::toRand({$object}, $gridX, $gridY)";
        }
    }
}