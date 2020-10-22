<?php

namespace ide\commands\theme;

use php\gui\UXNode;

abstract class CSSStyle
{
    /**
     * @return array
     */
    abstract public function getButtonCSS(): array;

    /**
     * @return array
     */
    abstract public function getMenuBarCSS(): array;

    /**
     * @return array
     */
    abstract public function getLabelCSS(): array;

    /**
     * @return array
     */
    abstract public function getBoxPanelCSS(): array;

    /**
     * @return array
     */
    abstract public function getSeparatorCSS(): array;

    /**
     * @return array
     */
    abstract public function getTextInputCSS(): array;

    /**
     * @return array
     */
    abstract public function getListViewCSS(): array;

    /**
     * @return array
     */
    abstract public function getTreeViewCSS(): array;

    /**
     * @return array
     */
    abstract public function getTabPaneCSS(): array;

    /**
     * @return array
     */
    abstract public function getSplitPaneCSS(): array;

    /**
     * @return array
     */
    abstract public function getScrollPaneCSS(): array;

    /**
     * @return array
     */
    abstract public function getFlowPaneCSS(): array;

    /**
     * @param UXNode $node
     * @param array $css
     */
    public static function applyCSSToNode(UXNode $node, array $css) {
        foreach ($css as $key => $value)
            $node->css($key, $value);
    }
}
