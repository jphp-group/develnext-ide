<?php


namespace ide\editors;


use php\gui\monaco\MonacoEditor;
use php\io\Stream;

class MonacoCodeEditor extends AbstractCodeEditor {

    /**
     * @var MonacoEditor
     */
    private $editor;

    private $__content;

    /**
     * MonacoCodeEditor constructor.
     * @param $file
     * @throws \php\io\IOException
     */
    public function __construct($file) {
        parent::__construct($file);

        $this->editor = new MonacoEditor();
        $this->editor->getEditor()->document->text = Stream::getContents($file);
        $this->editor->getEditor()->document->addTextChangeListener(function ($old, $new) use ($file) {
            Stream::putContents($file, $new);
        });
    }

    public function setReadOnly($readOnly)
    {
        parent::setReadOnly($readOnly);
        $this->editor->getEditor()->readOnly = $readOnly;
    }


    public function load() {
        // nope
    }

    public function save() {
        // nope
    }

    public function requestFocus() {
        // nope
    }

    public function loadContentToArea() {
        if ($this->__content != null)
            $this->editor->getEditor()->document->text = $this->__content;
    }

    public function loadContentToAreaIfModified() {
        $this->__content = Stream::getContents($this->file);
        if ($this->editor->getEditor()->document->text != $this->__content)
            $this->loadContentToArea();
    }

    /**
     * @inheritDoc
     */
    public function makeUi() {
        return $this->editor;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language) {
        $this->editor->getEditor()->currentLanguage = $language;
    }

    public function getValue(): string
    {
        $this->editor->getEditor()->document->text;
    }

    public function setValue(string $value): void
    {
        $this->editor->getEditor()->document->text = $value;
    }

    public function getSelectedText(): string
    {

    }

    public function undo()
    {
        // TODO: Implement undo() method.
    }

    public function redo()
    {
        // TODO: Implement redo() method.
    }

    public function copySelected()
    {
        // TODO: Implement copySelected() method.
    }

    public function cutSelected()
    {
        // TODO: Implement cutSelected() method.
    }

    public function pasteFromClipboard()
    {
        // TODO: Implement pasteFromClipboard() method.
    }

    public function jumpToLine(int $line, int $offset = 0)
    {
        // TODO: Implement jumpToLine() method.
    }
}
