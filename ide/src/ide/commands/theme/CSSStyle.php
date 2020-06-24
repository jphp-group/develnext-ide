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
     * @param UXNode $node
     * @param array $css
     */
    public static function applyCSSToNode(UXNode $node, array $css) {
        foreach ($css as $key => $value)
            $node->css($key, $value);
    }
}
