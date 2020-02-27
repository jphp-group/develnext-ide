<?php
namespace ide\editors\common;

use ide\Ide;
use php\gui\layout\UXHBox;
use php\gui\paint\UXColor;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXListCell;

class ObjectListEditorButtonRender
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

        $label = _(new UXLabel($item->prefix ? $item->prefix . '.{' . $item->text . '}' : $item->text));
        $label->graphic = Ide::get()->getImage($item->graphic, [16, 16]);
        $label->textColor = UXColor::of('black');

        if ($this->hideHint) {
            $cell->graphic = $label;
        } else {
            $hintLabel = _(new UXLabel($item->hint ? ": {{$item->hint}}" : ""));
            $hintLabel->textColor = UXColor::of('gray');

            $cell->graphic = new UXHBox([$label, $hintLabel]);
        }
    }
}