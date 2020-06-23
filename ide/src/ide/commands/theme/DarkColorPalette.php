<?php

namespace ide\commands\theme;

class DarkColorPalette extends ColorPalette
{
    /**
     * @return array
     */
    public function getButtonCSS(): array {
        return [
            "-fx-base" => "#333333",
            "-fx-text-fill" => "#ffffff"
        ];
    }

    /**
     * @return array|string[]
     */
    public function getMenuBarCSS(): array {
        return [
            "-fx-base" => "#000000",
            "-fx-background-color" => "#333333",
            "-fx-text-fill" => "#ffffff"
        ];
    }
}
