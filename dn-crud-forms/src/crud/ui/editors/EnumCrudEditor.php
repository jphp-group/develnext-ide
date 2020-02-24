<?php
namespace crud\ui\editors;

use php\gui\layout\UXHBox;
use php\gui\UXComboBox;
use php\gui\UXNode;
use php\lib\arr;

class EnumCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        $ui = new UXComboBox();
        $ui->maxWidth = 9999;

        $ui->items->addAll(
            flow($this->getField()->args)
            ->map(fn($el) => $this->getCrud()->t($el))
            ->toArray()
        );

        UXHBox::setHgrow($ui, 'ALWAYS');

        return $ui;
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        $args = $this->getField()->args;
        $i = 0; $found = false;
        foreach ($args as $code => $label) {
            if ($code === $value) {
                $found = true;
                break;
            }
            $i++;
        }

        /** @var UXComboBox $editorUi */
        $editorUi->selectedIndex = $found ? $i : -1;
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXComboBox $editorUi */
        if ($editorUi->selectedIndex === -1) {
            return null;
        }

        return arr::keys($this->getField()->args)[$editorUi->selectedIndex];
    }
}