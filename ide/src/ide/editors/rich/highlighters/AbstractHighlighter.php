<?php

namespace ide\editors\rich\highlighters;

use ide\editors\rich\CodeEditor;
use php\gui\event\UXKeyEvent;
use php\lib\str;

abstract class AbstractHighlighter {

    /**
     * @var CodeEditor
     */
    protected $editor;

    /**
     * @var string
     */
    protected $_text;

    /**
     * @var UXKeyEvent
     */
    protected $_event;

    public function __construct(CodeEditor $editor) {
        $this->editor = $editor;
    }

    public function doUpdate(UXKeyEvent $event) {
        $this->_text = $this->editor->getArea()->text;
        $this->_event = $event;

        $this->highlight();
    }

    abstract public function highlight();

    /**
     * Clear css style from editor
     */
    protected function clearStyle() {
        $this->editor->getArea()->clearStyle(0, str::length($this->editor->getArea()->text));
    }

    /**
     * Append fx-css class style
     *
     * @param int $form
     * @param int $to
     * @param string $class
     */
    protected function appendStyleClass(int $form, int $to, string $class) {
        $this->editor->getArea()->setStyleClass($form, $to, $class);
    }
}