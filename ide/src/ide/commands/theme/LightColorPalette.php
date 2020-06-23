<?php

namespace ide\commands\theme;

class LightColorPalette extends ColorPalette
{
    /**
     * @return array
     */
    public function getButtonCSS(): array {
        return [
            "-fx-base" => "#f3f3f3",
            "-fx-text-fill" => "#333333"
        ];
    }

    /**
     * @return array
     */
    public function getMenuBarCSS(): array {
        return [
            "-fx-base" => "#f3f3f3",
            "-fx-text-fill" => "#333333"
        ];
    }
}
