<?php

namespace php\gui\monaco;

abstract class Document {

    /**
     * @var string
     */
    public $text;

    /**
     * $callback(string $oldValue, $newValue)
     *
     * @param callable $callback
     */
    public function addTextChangeListener(callable $callback) {
    }
}
