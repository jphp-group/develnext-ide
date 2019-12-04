<?php


namespace ide\commands\theme\terminal;


class TextStyle {
    private $foreground;
    private $background;

    /**
     * TextStyle constructor.
     * @param $foreground
     * @param $background
     */
    public function __construct($foreground, $background) {
        $this->foreground = $foreground;
        $this->background = $background;
    }

    /**
     * @return mixed
     */
    public function getForeground() {
        return $this->foreground;
    }

    /**
     * @return mixed
     */
    public function getBackground() {
        return $this->background;
    }
}