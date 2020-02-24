<?php
namespace crud\ui\editors;

use php\gui\UXNode;

class IntegerCrudEditor extends StringCrudEditor
{
    public function setValueForUI(UXNode $editorUi, $value)
    {
        parent::setValueForUI($editorUi, (int) $value);
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        return (int) parent::getValueFromUI($editorUi);
    }
}