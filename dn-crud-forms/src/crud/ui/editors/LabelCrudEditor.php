<?php
namespace crud\ui\editors;

use php\gui\UXLabel;
use php\gui\UXNode;

class LabelCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        return new UXLabel();
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        /** @var UXLabel $editorUi */
        $editorUi->text = $value;
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXLabel $editorUi */
        return $editorUi->text;
    }
}