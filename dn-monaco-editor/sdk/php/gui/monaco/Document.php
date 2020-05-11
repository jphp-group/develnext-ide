<?php

namespace php\gui\monaco;

abstract class Document {

    /**
     * @var string
     */
    public ?string $text;

    /**
     * $callback(string $oldValue, $newValue)
     *
     * @param callable $callback
     */
    public function addTextChangeListener(callable $callback) {
    }

    /**
     * @param array $range
     * @return string
     */
    public function getTextInRange($range): string {
        return "Some text :^)";
    }
}
