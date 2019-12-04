<?php

namespace ide\commands\theme\terminal;


use php\gui\paint\UXColor;
use php\intellij\ui\SettingsProvider;

abstract class TerminalTheme {

    /**
     * @return string
     */
    public function getFont(): string {
        return null;
    }

    /**
     * @return int
     */
    public function getFontSize(): int {
        return 14;
    }

    /**
     * @return int
     */
    public function getLineSpace(): int {
        return 0;
    }

    /**
     * @return TextStyle
     */
    public function getDefaultStyle(): TextStyle {
        return new TextStyle("black", "white");
    }

    /**
     * @return TextStyle
     */
    public function getSelectionColor(): TextStyle {
        return new TextStyle("white", UXColor::rgb(82, 109, 165));
    }

    /**
     * @return TextStyle
     */
    public function getFoundPatternColor(): TextStyle {
        return new TextStyle("black", UXColor::rgb(255, 255, 0));
    }

    /**
     * @return TextStyle
     */
    public function getHyperlinkColor(): TextStyle {
        return new TextStyle("blue", "white");
    }

    /**
     * @return SettingsProvider
     */
    public function build(): SettingsProvider {
        $sp = new SettingsProvider();

        if ($this->getFont() != null) {
            $sp->put("font", $this->getFont());
        }

        if ($this->getFontSize() != null) {
            $sp->put("fontSize", $this->getFontSize());
        }

        if ($this->getLineSpace() != null) {
            $sp->put("lineSpace", $this->getLineSpace());
        }

        if ($this->getDefaultStyle() != null) {
            $sp->put("defaultStyleForeground", $this->getColor($this->getDefaultStyle()->getForeground()));
            $sp->put("defaultStyleBackground", $this->getColor($this->getDefaultStyle()->getBackground()));
        }

        if ($this->getSelectionColor() != null) {
            $sp->put("selectionColorForeground", $this->getColor($this->getSelectionColor()->getForeground()));
            $sp->put("selectionColorBackground", $this->getColor($this->getSelectionColor()->getBackground()));
        }

        if ($this->getFoundPatternColor() != null) {
            $sp->put("foundPatternColorForeground", $this->getColor($this->getFoundPatternColor()->getForeground()));
            $sp->put("foundPatternColorBackground", $this->getColor($this->getFoundPatternColor()->getBackground()));
        }

        if ($this->getHyperlinkColor() != null) {
            $sp->put("hyperlinkColorForeground", $this->getColor($this->getHyperlinkColor()->getForeground()));
            $sp->put("hyperlinkColorBackground", $this->getColor($this->getHyperlinkColor()->getBackground()));
        }

        return $sp;
    }

    private function getColor($color): UXColor {
        if ($color instanceof UXColor) return $color;
        return UXColor::of($color);
    }
}