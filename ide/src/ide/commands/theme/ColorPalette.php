<?php

namespace ide\commands\theme;

use php\gui\UXNode;

abstract class ColorPalette
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
     * @param UXNode $node
     * @param array $css
     */
    public static function applyCSSToNode(UXNode $node, array $css) {
        foreach ($css as $key => $value)
            $node->css($key, $value);
    }
}
