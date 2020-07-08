<?php

namespace ide\commands\theme;

class DarkCSSStyle extends CSSStyle {

    /**
     * @return array
     */
    public function getButtonCSS(): array {
        return [
            "-fx-base" => "#333333",
            "-fx-control-inner-background" => "-fx-base",
            "-fx-control-inner-background-alt" => "derive(-fx-base, 4%)",
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill"
        ];
    }

    /**
     * @return array
     */
    public function getMenuBarCSS(): array {
        return [
            "-fx-base" => "#333333",
            "-fx-background-color" => "#333333",
            "-fx-control-inner-background" => "-fx-base",
            "-fx-control-inner-background-alt" => "derive(-fx-base, 4%)",
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill"
        ];
    }

    /**
     * @return array
     */
    public function getLabelCSS(): array {
        return [
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill",
            "-fx-base" => "gray"
        ];
    }

    /**
     * @return array
     */
    public function getBoxPanelCSS(): array {
        return [
            "-fx-background-color" => "#393939",
            "-fx-border-width" => "0"
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

    /**
     * @return array
     */
    public function getTextInputCSS(): array {
        return [
            "-fx-control-inner-background" => "#333333",
            "-fx-base" => "#333333",
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill",
            "-fx-prompt-text-fill" => "#a0a0a0"
        ];
    }

    /**
     * @return array
     */
    public function getListViewCSS(): array {
        return [
            "-fx-base" => "#333333",
            "-fx-control-inner-background" => "-fx-base",
            "-fx-control-inner-background-alt" => "derive(-fx-base, 4%)",
            "-dn-selected-background-color" => "derive(-fx-base, -30%)",
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill",
        ];
    }

    /**
     * @return array
     */
    public function getTreeViewCSS(): array {
        return [
            "-fx-base" => "#333333",
            "-fx-control-inner-background" => "-fx-base",
            "-fx-control-inner-background-alt" => "derive(-fx-base, 4%)",
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill",
        ];
    }

    /**
     * @return array
     */
    public function getTabPaneCSS(): array {
        return [
            "-dn-base" => "#333333",
            "-dn-text-fill" => "#C7D3DA",
            "-fx-text-fill" => "-dn-text-fill",
            "-dn-tab-header-area-background" => "derive(-dn-base, -30%)",
            "-dn-tab-content-area-background" => "-dn-base",
            "-dn-tab-header-background" => "-dn-base"
        ];
    }

    /**
     * @return array
     */
    public function getSplitPaneCSS(): array {
        return [
            "-dn-base" => "#333333",
            "-fx-background-color" => "none"
        ];
    }

    /**
     * @return array
     */
    public function getScrollPaneCSS(): array {
        return [
            "-dn-base" => "#333333"
        ];
    }
}
