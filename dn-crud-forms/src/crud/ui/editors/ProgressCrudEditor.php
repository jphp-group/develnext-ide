<?php
namespace crud\ui\editors;

use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\gui\UXProgressBar;

class ProgressCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        $bar = new UXProgressBar();
        $bar->height = 25;
        $bar->maxHeight = 9999;
        $bar->maxWidth = 9999;
        $bar->classes->add('pr');
        UXHBox::setHgrow($bar, 'ALWAYS');
        UXVBox::setVgrow($bar, 'ALWAYS');

        if ($this->getField()->label) {
            $box = new UXVBox([new UXLabel($this->getField()->label . ":"), $bar], 3);
            return $box;
        } else {
            $box = new UXVBox([$bar], 3);
        }

        return $box;
    }

    public function isWithoutLabel(): bool
    {
        return true;
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        /** @var UXProgressBar $editorUi */
        $editorUi->lookup('.pr')->progressK = (float) $value;
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXProgressBar $editorUi */
        return $editorUi->lookup('.pr')->progressK;
    }
}