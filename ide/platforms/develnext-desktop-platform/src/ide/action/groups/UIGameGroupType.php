<?php
namespace ide\action\groups;

use ide\action\AbstractActionTypeGroup;

class UIGameGroupType extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'game';
    }

    public function getName(): string
    {
        return 'action.type.ui.game';
    }

    public function getSubGroups(): array
    {
        return flow(
            parent::getSubGroups(), [
                'move' => 'action.type.group.moving',
                'anim' => 'action.type.group.animation',
            ]
        )->toMap();
    }
}