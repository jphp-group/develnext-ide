<?php
namespace ide\action\types\game;

use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\MixedArgumentEditor;
use ide\editors\argument\ObjectArgumentEditor;
use ide\editors\common\ObjectListEditor;
use ide\editors\common\ObjectListEditorItem;
use ide\formats\form\elements\FormFormElement;
use ide\formats\form\elements\GamePaneFormElement;
use ide\formats\form\elements\PanelFormElement;
use php\lib\str;

class CreateInstanceActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return 'game';
    }

    function attributes()
    {
        return [
            'id' => 'prototype',
            'x' => 'integer',
            'y' => 'integer',
            'parent' => 'object',
            'relative' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'id' => 'wizard.object.prototype::Объект (прототип)',
            'x' => 'wizard.x.position',
            'y' => 'wizard.y.position',
            'parent' => 'wizard.relative.to.object::Относительно какого объекта',
            'relative' => 'wizard.relative::Относительно',
        ];
    }

    function attributeSettings()
    {
        return [
            'id' => ['def' => '~sender'],
            'x' => ['def' => '0'],
            'y' => ['def' => '0'],
        ];
    }

    function getTagName()
    {
        return "createInstance";
    }

    function getTitle(Action $action = null)
    {
        return _("wizard.2d.command.create.instance::Создать {0} ", $action ? $action->get('id') : 'клона');
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            $parent = $action->get('parent');

            if ($parent) {
                return _(
                    "wizard.2d.command.desc.param.create.instance.parent::Создать клона от объекта {0}, относительно = {1}, [x, y] = [{2}, {3}], относительно объекта {4}.",
                    $action->get('id'), $action->relative ? _('btn.yes') : _('btn.no'), $action->get('x'), $action->get('y'), $parent
                );
            } else {
                return _(
                    "wizard.2d.command.desc.param.create.instance::Создать клона от объекта {0}, относительно = {2}, [x, y] = [{2}, {3}]",
                    $action->get('id'), $action->relative ? _('btn.yes') : _('btn.no'), $action->get('x'), $action->get('y')
                );
            }
        } else {
            return "wizard.2d.command.desc.create.instance::Создать клона от объекта";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/idea16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $x = $action->get('x');
        $y = $action->get('y');
        $parent = $action->parent ? $action->get('parent') : ($action->relative ? '$e->sender' : 'null');

        $actionScript->addLocalVariable('instance');

        if (!$action->relative) {
            if ($x == 0 && $y == 0) {
                return "\$instance = \$this->create({$action->get('id')}, $parent)";
            } else {
                return "\$instance = \$this->create({$action->get('id')}, $parent, $x, $y)";
            }
        } else {
            if ($x == 0 && $y == 0) {
                return "\$instance = \$this->create({$action->get('id')}, $parent)";
            } else {
                return "\$instance = \$this->create({$action->get('id')}, $parent, $x, $y)";
            }
        }
    }
}