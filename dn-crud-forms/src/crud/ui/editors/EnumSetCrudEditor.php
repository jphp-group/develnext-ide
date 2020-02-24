<?php
namespace crud\ui\editors;

use php\gui\layout\UXHBox;
use php\gui\UXCheckbox;
use php\gui\UXComboBox;
use php\gui\UXNode;
use php\lib\arr;

class EnumSetCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        $box = new UXHBox([], 5);

        foreach ($this->getField()->args as $code => $value) {
            $checkbox = new UXCheckbox($this->getCrud()->t($value));
            $checkbox->data('code', $code);
            $checkbox->classes->add("value-$code");
            $box->add($checkbox);
        }

        $box->alignment = 'BASELINE_LEFT';

        return $box;
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        /** @var UXHBox $editorUi */
        foreach ($editorUi->children as $child) {
            $child->selected = false;
        }

        foreach ((array) $value as $one) {
            $checkbox = $editorUi->lookup(".value-$one");
            if ($checkbox) {
                $checkbox->selected = true;
            }
        }
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXHBox $editorUi */
        $r = [];

        foreach ($editorUi->children as $child) {
            if ($child->selected) $r[] = $child->data('code');
        }

        return $r;
    }
}