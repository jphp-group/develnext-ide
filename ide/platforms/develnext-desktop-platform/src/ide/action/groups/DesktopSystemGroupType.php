<?php
namespace ide\action\groups;


use ide\action\AbstractActionTypeGroup;

class DesktopSystemGroupType extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'system';
    }

    public function getName(): string
    {
        return 'action.type.desktop.system';
    }
}