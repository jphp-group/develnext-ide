<?php
namespace ide\action\groups;

use ide\action\AbstractActionTypeGroup;

class UIActionGroupType extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'ui';
    }

    public function getName(): string
    {
        return 'action.type.desktop.ui';
    }

    public function getSubGroups(): array
    {
        return flow(
            parent::getSubGroups(), [
                'behaviour' => 'action.type.desktop.group.behaviour',
                'anim' => 'action.type.group.animation',
                'move' => 'action.type.group.moving',
                'object' => 'action.type.group.object',
                'form' => 'action.type.group.form'
            ]
        )->toMap();
    }
}