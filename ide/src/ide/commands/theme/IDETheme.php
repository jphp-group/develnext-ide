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

    /**
     * @return CSSStyle
     */
    abstract public function getCSSStyle(): CSSStyle;

    public function onApply() {
        // something ...
    }
}