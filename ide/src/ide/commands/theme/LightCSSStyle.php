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
        return [
            "-dn-base" => "#f3f3f3",
            "-dn-selected-background-color" => "derive(-dn-base, -15%)",
        ];
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
            "-dn-base" => "#F4F4F4",
            "-dn-text-fill" => "#333333",
            "-dn-tab-header-area-background" => "derive(-dn-base, 30%)",
            "-dn-tab-content-area-background" => "-dn-base",
            "-dn-tab-header-background" => "-dn-base"
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

    /**
     * @return array
     */
    public function getScrollPaneCSS(): array {
        return [
            "-dn-base" => "#f3f3f3"
        ];
    }

    /**
     * @return array
     */
    public function getFlowPaneCSS(): array {
        return [
            "-dn-base" => "#f3f3f3",
            "-fx-border-color" => "derive(-dn-base, 30%)",
            "-fx-border-style" => "solid",
            "-fx-border-width" => "1px"
        ];
    }
}
