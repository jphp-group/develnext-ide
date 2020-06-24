<?php

namespace ide\commands\theme;

class LightCSSStyle extends CSSStyle
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

    /**
     * @return array
     */
    public function getLabelCSS(): array {
        return [
            "-fx-text-fill" => "#333333"
        ];
    }

    /**
     * @return array
     */
    public function getBoxPanelCSS(): array {
        return [];
    }

    /**
     * @return array
     */
    public function getSeparatorCSS(): array {
        return [];
    }

    /**
     * @return array
     */
    public function getTextInputCSS(): array {
        return [];
    }

    /**
     * @return array
     */
    public function getListViewCSS(): array {
        return [];
    }

    /**
     * @return array
     */
    public function getTreeViewCSS(): array {
        return [];
    }

    /**
     * @return array
     */
    public function getTabPaneCSS(): array {
        return [
            "-dn-base" => "#f3f3f3",
            "-dn-text-fill" => "#333333"
        ];
    }

    /**
     * @return array
     */
    public function getSplitPaneCSS(): array {
        return [
            "-dn-base" => "#f3f3f3"
        ];
    }
}
