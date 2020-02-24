<?php
namespace crud\ui\editors;

use php\gui\UXCheckbox;
use php\gui\UXNode;
use php\gui\UXTextField;

class BooleanCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        return new UXCheckbox();
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        /** @var UXCheckbox $editorUi */
        $editorUi->selected = (bool) $value;
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXCheckbox $editorUi */
        return $editorUi->selected;
    }

    public function isLeftSideUI(): bool
    {
        return true;
    }
}