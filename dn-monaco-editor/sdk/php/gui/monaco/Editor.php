<?php

namespace php\gui\monaco;

abstract class Editor {

    /**
     * @var string
     */
    public $currentTheme, $currentLanguage;

    /**
     * @var Document
     */
    public $document;

    /**
     * @return ViewController
     */
    public function getViewController(): ViewController {
    }
}
