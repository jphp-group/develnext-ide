<?php
namespace ide\action\groups;

use ide\action\AbstractActionTypeGroup;

class UIMediaGroupType extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'media';
    }

    public function getName(): string
    {
        return 'action.type.desktop.media';
    }

    public function getSubGroups(): array
    {
        return flow(
            parent::getSubGroups(), [
                'audio' => 'action.type.desktop.group.audio',
            ]
        )->toMap();
    }
}