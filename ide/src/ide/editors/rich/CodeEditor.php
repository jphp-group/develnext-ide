<?php

namespace ide\editors\rich;

use ide\editors\rich\highlighters\AbstractHighlighter;
use ide\Ide;
use ide\Logger;
use php\gui\event\UXKeyEvent;
use php\gui\UXGenericStyledArea;
use php\gui\UXStyleClassedTextArea;
use php\gui\UXVirtualizedScrollPane;
use php\io\File;
use php\lib\fs;
use php\lib\str;

class CodeEditor extends UXVirtualizedScrollPane {

    /**
     * @var UXStyleClassedTextArea
     */
    protected $codeArea;

    /**
     * @var LineNumber
     */
    protected $lineNumber;

    /**
     * @var AbstractHighlighter[]
     */
    private $highlighters;

    /**
     * CodeEditor constructor.
     * @throws \Exception
     */
    public function __construct() {
        parent::__construct(
            $this->codeArea = new UXStyleClassedTextArea());

        $this->codeArea->on("keyUp", function (UXKeyEvent $event) {
            $this->getArea()->clearStyle(0, str::length($this->getArea()->text));

            foreach ($this->highlighters as $highlighter)
                $highlighter->doUpdate($event);
        });

        $this->codeArea->classes->add("syntax-text-area");

        // TODO: make multi-theme
        $this->codeArea->stylesheets->add(".theme/editor/default.css");
    }

    /**
     * Add highlighter by class name
     *     -> SimpleHighlighter::class
     *
     * @param string $class
     */
    public function addHighlighter(string $class) {
        try {
            $this->highlighters[$class] = new $class($this);
        } catch (\Throwable $exception) {
            Logger::error("Error adding highlighter, message error: " . $exception->getMessage());
        }
    }

    /**
     * Set LineNumber for code editor
     *
     * @param LineNumber $lineNumber
     */
    public function setLineNumber(LineNumber $lineNumber) {
        $this->getArea()->clearGraphicFactory();
        $this->getArea()->setGraphicFactory(
            $this->lineNumber = $lineNumber);
    }

    /**
     * @return LineNumber|null
     */
    public function getLineNumber() : ?LineNumber {
        return $this->lineNumber;
    }

    /**
     * Return base editor (UXGenericStyledArea)
     *
     * @return UXStyleClassedTextArea
     */
    public function getArea() : UXStyleClassedTextArea {
        return $this->codeArea;
    }
}