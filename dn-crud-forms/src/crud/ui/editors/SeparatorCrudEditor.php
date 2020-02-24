<?php
namespace crud\ui\editors;

use php\gui\layout\UXHBox;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\gui\UXSeparator;

class SeparatorCrudEditor extends AbstractCrudEditor
{
    public function makeUI(): UXNode
    {
        $label = $this->getField()->label;

        $UXSeparator = new UXSeparator();
        $UXLabel = new UXLabel($this->getCrud()->t($label));
        $UXLabel->font->bold = true;

        $UXHBox = new UXHBox([$UXLabel, $UXSeparator], 5);
        $UXHBox->alignment = 'BASELINE_LEFT';
        $UXHBox->padding = [10, 0];

        UXHBox::setHgrow($UXSeparator, 'ALWAYS');
        return $UXHBox;
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
    }

    public function getValueFromUI(UXNode $editorUi)
    {
    }

    public function isWithoutLabel(): bool
    {
        return true;
    }
}