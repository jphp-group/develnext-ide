<?php
namespace ide\action\groups;

use ide\action\AbstractActionTypeGroup;

class ConditionsActionTypeGroup extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'conditions';
    }

    public function getName(): string
    {
        return 'action.type.conditions';
    }
}