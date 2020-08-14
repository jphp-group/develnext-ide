<?php

namespace ide\editors;

use Exception;
use ide\commands\ChangeThemeCommand;
use ide\commands\theme\IDETheme;
use ide\commands\theme\LightTheme;
use ide\editors\menu\ContextMenu;
use ide\Logger;
use ide\ui\elements\DNAnchorPane;
use ide\ui\elements\DNLabel;
use ide\utils\FileUtils;
use php\concurrent\Promise;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\monaco\MonacoEditor;
use php\gui\UXClipboard;
use php\gui\UXLabel;

class MonacoCodeEditor extends AbstractCodeEditor {
    private MonacoEditor $editor;
    private $__content;

    /**
     * MonacoCodeEditor constructor.
     * @param $file
     * @throws Exception
     */
    public function __construct($file) {
        parent::__construct($file);
        $this->editor = new MonacoEditor();

        $this->loadContentToAreaIfModified()->then(function () {
            $this->editor->getEditor()->document->addTextChangeListener(function ($old, $new) {
                FileUtils::putAsync($this->file, $new);
            });
        })->catch(function () use ($file) {
            Logger::error("Failed to load content to monaco editor from {$file}");
            $this->setReadOnly(true);
        });

        $applyEditorTheme = function (IDETheme $theme) {
            if ($theme instanceof LightTheme) {
                $this->editor->getEditor()->currentTheme = "vs-light";
            } else {
                $this->editor->getEditor()->currentTheme = "vs-dark";
            }
        };

        ChangeThemeCommand::$instance->bind("setCurrentTheme", $applyEditorTheme);
        $applyEditorTheme(ChangeThemeCommand::$instance->getCurrentTheme());
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
        $this->editor->getEditor()->focus();
    }

    public function loadContentToArea() {
        if ($this->__content != null) {
            $this->editor->getEditor()->document->text = $this->__content;
        }
    }

    public function loadContentToAreaIfModified(): Promise {
        return FileUtils::getAsync($this->file, function ($data) {
            $this->__content = $data;

            if ($this->editor->getEditor()->document->text != $this->__content) {
                $this->loadContentToArea();
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function makeEditorUi() {
        $panel = new DNAnchorPane();
        $loadingLabel = _(new DNLabel("code.editor.loading"));
        $loadingLabel->font = $loadingLabel->font->withSize(16);

        $loadingBox = new UXHBox([
            ico("wait32"),
            $loadingLabel
        ], 8);
        $loadingBox->alignment = "CENTER";

        UXAnchorPane::setAnchor($loadingBox, 0);
        UXAnchorPane::setAnchor($this->editor, 0);
        $panel->add($loadingBox);
        $panel->add($this->editor);

        if ($this->commands) {
            $this->editor->contextMenu = (new ContextMenu($this, $this->commands))->getRoot();
        }

        return $panel;
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
        $editor = $this->editor;
        return $editor->getEditor()->document->getTextInRange($editor->getEditor()->getSelection());
    }

    public function undo()
    {
        $this->editor->getEditor()->undo();
    }

    public function redo()
    {
        $this->editor->getEditor()->redo();
    }

    public function copySelected()
    {
        $this->editor->getEditor()->copy();
    }

    public function cutSelected()
    {
        $this->editor->getEditor()->cut();
    }

    public function pasteFromClipboard()
    {
        $this->editor->getEditor()->paste();
    }

    public function jumpToLine(int $line, int $offset = 0)
    {
        $this->editor->getEditor()->revealLineInCenter($line);
    }
}
