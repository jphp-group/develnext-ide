<?php

namespace ide\editors\rich;

use php\gui\layout\UXHBox;
use php\gui\UXImage;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXNode;

class LineNumber {

    /**
     * @var UXImageView[]
     */
    private $gutters;

    /**
     * Implement __invoke because setGraphicFactory( callback(int) : UXNode )
     */
    public function __invoke(int $line) : UXNode {
        $node = new UXLabel($line + 1);
        $node->alignment = "BASELINE_RIGHT";
        $node->paddingRight = 10;
        $node->classes->add("lineno");
        $node->width = 50;

        if (!$this->gutters[$line + 1]) {
            $gutter = &$this->gutters[$line + 1] = new UXImageView();
            $gutter->size = [16, 16];
        } else $gutter = &$this->gutters[$line + 1];

        $box = new UXHBox([$gutter, $node]);
        $box->classes->add("gutter");
        $box->spacing = $box->paddingLeft = 8;

        return $box;
    }

    /**
     * Add UXImage with callback to line
     *
     * @param int $line
     * @param UXImage $image
     * @param callable $callback
     */
    public function addGutter(int $line, UXImage $image, callable $callback) {
        $this->gutters[$line] = new UXImageView($image);
        $this->gutters[$line]->on("click", $callback);
        $this->gutters[$line]->cursor = "HAND";
        $this->gutters[$line]->size = [16, 16];
    }

    /**
     * @param int $line
     */
    public function removeGutter(int $line) {
        $this->gutters[$line] = new UXImageView();
    }
}