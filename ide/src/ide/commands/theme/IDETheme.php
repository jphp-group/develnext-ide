<?php

namespace ide\commands\theme;

abstract class IDETheme {

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getAuthor(): string;

    /**
     * @return string
     */
    abstract public function getCSSFile(): string;

    abstract public function getTerminalTheme();

    public function onApply() {
        // something ...
    }
}