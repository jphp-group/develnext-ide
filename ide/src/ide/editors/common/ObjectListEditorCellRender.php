<?php
namespace ide\editors\common;

use ide\Ide;
use ide\ui\elements\DNLabel;
use php\gui\layout\UXHBox;
use php\gui\paint\UXColor;
use php\gui\UXListCell;

class ObjectListEditorCellRender
{
    protected $hideHint = false;

    public function hideHint()
    {
        $this->hideHint = true;
    }

    public function __invoke(UXListCell $cell, ObjectListEditorItem $item)
    {
        $cell->graphic = null;
        $cell->text = null;

        $label = _(new DNLabel($item->text));
        $label->graphic = Ide::get()->getImage($item->graphic, [16, 16]);

        $label->paddingLeft = $item->level * 10;

        if ($this->hideHint) {
            $cell->graphic = $label;
        } else {
            if ($item->hint) {
                $hintLabel = _(new DNLabel(": {{$item->hint}}"));
            } else {
                $hintLabel = new DNLabel("");
            }

            $hintLabel->textColor = UXColor::of('gray');

            $cell->graphic = new UXHBox([$label, $hintLabel]);
        }
    }
}