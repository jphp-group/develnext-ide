<?php
namespace crud\ui\editors;

use php\gui\UXNode;
use php\gui\UXTextField;

class StringCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        return new UXTextField();
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        /** @var UXTextField $editorUi */
        $editorUi->text = $value;
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXTextField $editorUi */
        return $editorUi->text;
    }
}