<?php

namespace ide\commands\theme;

class DarkCSSStyle extends CSSStyle
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
     * @return array
     */
    public function getMenuBarCSS(): array {
        return [
            "-fx-base" => "#000000",
            "-fx-background-color" => "#333333",
            "-fx-text-fill" => "#ffffff"
        ];
    }

    /**
     * @return array
     */
    public function getLabelCSS(): array {
        return [
            "-fx-text-fill" => "#ffffff"
        ];
    }

    /**
     * @return array
     */
    public function getBoxPanelCSS(): array {
        return [
            "-fx-background-color" => "#393939"
        ];
    }

    /**
     * @return array
     */
    public function getSeparatorCSS(): array {
        return [
            "-fx-base" => "#333333",
        ];
    }
}
