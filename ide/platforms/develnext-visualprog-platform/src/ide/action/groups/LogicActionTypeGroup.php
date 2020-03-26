<?php
namespace ide\action\groups;

use ide\action\AbstractActionTypeGroup;

class LogicActionTypeGroup extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'logic';
    }

    public function getName(): string
    {
        return 'action.type.logic';
    }

    public function getSubGroups(): array
    {
        return flow(parent::getSubGroups(), [
            'data' => 'action.type.group.data',
            'decor' => 'action.type.group.decoration'
        ])->toMap();
    }
}