<?php

namespace ide\editors\rich;

use ide\editors\rich\highlighters\AbstractHighlighter;
use ide\Logger;
use php\gui\UXHighlightClassedTextArea;
use php\gui\UXStyleClassedTextArea;
use php\gui\UXStyleSpansBuilder;
use php\gui\UXVirtualizedScrollPane;

class RichCodeEditor extends UXVirtualizedScrollPane {

    /**
     * @var UXHighlightClassedTextArea
     */
    protected $codeArea;

    /**
     * @var LineNumber
     */
    protected $lineNumber;

    /**
     * @var AbstractHighlighter
     */
    private $highlighter;

    /**
     * RichCodeEditor constructor.
     * @throws \Exception
     */
    public function __construct() {
        parent::__construct(
            $this->codeArea = new UXHighlightClassedTextArea());

        $this->codeArea->classes->add("syntax-text-area");

        $this->codeArea->setHighlightCallback(500, function () {
            $builder = new UXStyleSpansBuilder();

            if ($this->highlighter)
                $this->highlighter->doUpdate($builder);

            return $builder;
        });

        // TODO: make multi-theme
        $this->codeArea->stylesheets->add(".theme/editor/dark.css");
    }

    /**
     * Add highlighter by class name
     *     -> SimpleHighlighter::class
     *
     * @param string $class
     */
    public function setHighlighter(string $class) {
        try {
            $this->highlighter = new $class($this);
        } catch (\Throwable $exception) {
            Logger::error("Error setting highlighter, message error: " . $exception->getMessage());
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