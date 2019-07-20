<?php

namespace ide\editors\rich\highlighters;

use ide\editors\rich\RichCodeEditor;
use php\gui\UXStyleSpansBuilder;

abstract class AbstractHighlighter {

    /**
     * @var RichCodeEditor
     */
    protected $editor;

    /**
     * @var string
     */
    protected $_text;

    public function __construct(RichCodeEditor $editor) {
        $this->editor = $editor;
    }

    public function doUpdate(UXStyleSpansBuilder $builder) {
        $this->_text = $this->editor->getArea()->text;

        $this->highlight($builder);
    }

    abstract public function highlight(UXStyleSpansBuilder $builder);
}