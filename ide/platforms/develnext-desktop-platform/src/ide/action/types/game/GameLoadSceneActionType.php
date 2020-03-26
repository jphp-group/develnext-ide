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
use php\lib\str;

class GameLoadSceneActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function attributes()
    {
        return [
            'dest' => 'form',
            'source' => 'object',
        ];
    }

    function attributeLabels()
    {
        return [
            'dest' => 'wizard.2d.scene::Сцена',
            'source' => 'wizard.object.for.load.2d.scene::Объект (куда загрузить сцену)'
        ];
    }

    function attributeSettings()
    {
        return [
            'source' => ['def' => '~senderForm', 'editor' => function ($name, $label) {
                $editor = new ObjectArgumentEditor([
                    'formMethod'   => 'originForm',
                    'objectFilter' => function (ObjectListEditorItem $item) {
                        return $item->element instanceof GamePaneFormElement
                            || !$item->element;
                    }
                ]);
                return $editor;
            }],
        ];
    }

    function getTagName()
    {
        return "gameLoadScene";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.2d.command.load.2d.scene::Загрузить сцену";
    }

    function getDescription(Action $action = null)
    {
        return _("wizard.2d.command.desc.load.2d.scene::Загрузить игровую сцену из формы {0} в объект {1} ", $action ? $action->get('dest') : '', $action ? $action->get('source') : '');
    }

    function getIcon(Action $action = null)
    {
        return 'icons/cinema16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "{$action->get('source')}->phys->loadScene({$action->get('dest')})";
    }
}