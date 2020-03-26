<?php
namespace ide\action\groups;

use ide\action\AbstractActionTypeGroup;

class UIFormsActionGroupType extends AbstractActionTypeGroup
{
    public function getCode(): string
    {
        return 'ui-forms';
    }

    public function getName(): string
    {
        return 'action.type.desktop.ui.forms';
    }
}