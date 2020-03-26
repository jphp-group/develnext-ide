<?php
namespace ide\action\types\game;

use game\Jumping;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\ObjectArgumentEditor;
use ide\editors\common\ObjectListEditorItem;
use ide\formats\form\elements\FormFormElement;
use ide\formats\form\elements\GamePaneFormElement;
use ide\formats\form\elements\SpriteViewFormElement;
use php\lib\str;

class SetGravityActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function getSubGroup()
    {
        return 'move';
    }

    function getHelpText()
    {
        return 'wizard.2d.command.set.gravity.help.text::Это действие работает только для объектов с поведениями `Объект игровой сцены` и `Игровая сцена`!';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'x' => 'float',
            'y' => 'float',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object',
            'x' => 'wizard.x.gravity.hor::Гравитация по X (горизонтальная)',
            'y' => 'wizard.x.gravity.ver::Гравитация по Y (вертикальная)'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender', 'editor' => function () {
                return new ObjectArgumentEditor([
                    'objectFilter' => function (ObjectListEditorItem $item) {
                        return $item->element instanceof SpriteViewFormElement
                        || $item->element instanceof FormFormElement || $item->element == null
                            || $item->element instanceof GamePaneFormElement;
                    }
                ]);
            }],
            'x' => ['def' => 0],
            'y' => ['def' => 0],
        ];
    }

    function getTagName()
    {
        return "setGravity";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.2d.command.set.gravity::Изменить гравитацию";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            return _("wizard.2d.command.desc.param.set.gravity::Изменить гравитацию {0} объекта на (x: {1}, y: {2})", $action->get('object'), $action->get('x'), $action->get('y'));
        } else {
            return "wizard.2d.command.desc.set.gravity::Изменить гравитацию объекта";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/gravity16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "{$action->get('object')}->phys->gravity = [{$action->get('x')}, {$action->get('y')}]";
    }
}