<?php

namespace ide\editors;

use develnext\lexer\inspector\AbstractInspector;
use Exception;
use ide\autocomplete\AutoComplete;
use ide\autocomplete\AutoCompleteInsert;
use ide\autocomplete\AutoCompleteItem;
use ide\autocomplete\ConstantAutoCompleteItem;
use ide\autocomplete\FunctionAutoCompleteItem;
use ide\autocomplete\MethodAutoCompleteItem;
use ide\autocomplete\PropertyAutoCompleteItem;
use ide\autocomplete\StatementAutoCompleteItem;
use ide\autocomplete\VariableAutoCompleteItem;
use ide\commands\ChangeThemeCommand;
use ide\commands\theme\IDETheme;
use ide\commands\theme\LightTheme;
use ide\editors\menu\ContextMenu;
use ide\Ide;
use ide\Logger;
use ide\ui\elements\DNAnchorPane;
use ide\ui\elements\DNLabel;
use ide\utils\FileUtils;
use php\concurrent\Promise;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\monaco\CompletionItem;
use php\gui\monaco\MonacoEditor;
use php\gui\UXClipboard;
use php\gui\UXLabel;
use php\lib\arr;
use php\lib\char;
use php\lib\fs;
use php\lib\str;
use php\util\Flow;

class MonacoCodeEditor extends AbstractCodeEditor
{
    private MonacoEditor $editor;
    private $__content;

    /**
     * @var AutoComplete
     */
    protected $autoComplete;

    protected $autoCompleteTypes = [];

    /**
     * MonacoCodeEditor constructor.
     * @param $file
     * @param array $options
     * @throws Exception
     */
    public function __construct($file, array $options = [])
    {
        parent::__construct($file);
        $this->editor = new MonacoEditor();

        $this->loadContentToAreaIfModified()->then(function () {
            $this->editor->getEditor()->document->addTextChangeListener(function ($old, $new) {
                $editor = $this->editor->getEditor();
                $position = $editor->getPosition();
                $this->autoComplete->update($this->getValue(), $editor->getPositionOffset(), $position['lineNumber'], $position['column']);

                FileUtils::putAsync($this->file, $new)->then(function () {
                    $this->fileTime = $this->file;
                });
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


        if ($options['autoComplete'] instanceof AutoComplete) {
            $this->autoComplete = $options['autoComplete'];
        } else if (is_array($options['autoComplete'])) {
            if ($project = Ide::project()) {
                $bindId = str::random();

                if ($inspector = $project->getInspector($options['autoComplete']['context'])) {
                    $class = $options['autoComplete']['class'];
                    $this->autoComplete = new $class($inspector);
                } else {
                    $project->on('registerInspector', function ($context, AbstractInspector $inspector) use ($options, $bindId, $project) {
                        if ($context === $options['autoComplete']['context']) {
                            $class = $options['autoComplete']['class'];
                            $this->autoComplete = new $class($inspector);
                            $project->off('registerInspector', $bindId);
                        }

                    }, $bindId);
                }
            }
        }

        $this->editor->setOnLoad(function () {
            $this->editor->getEditor()->registerCompletionItemProvider("php", ": $ >", function ($positionAndRange) {
                if ($string = $this->getAutocompleteString()) {
                    $items = null;

                    $region = $this->autoComplete->findRegion($positionAndRange["position"]["lineNumber"], $positionAndRange["position"]["column"]);
                    $types = $this->autoComplete->identifyType($string, $region);

                    if (arr::keys($this->autoCompleteTypes) != $types) {
                        $this->autoCompleteTypes = [];

                        foreach ($types as $type) {
                            //if (!$this->hasType($type)) {
                            $this->addAutocompleteType($type);
                            //}
                        }
                    }

                    $prefix = $this->getAutocompleteString(true);

                    $items = $this->makeAutocompleteItems($prefix, $positionAndRange["position"]);
                    $result = [];
                    /** @var AutoCompleteItem $item */
                    foreach ($items as $item) {
                        $insert = $item->getInsert();
                        if (!is_string($insert) && is_callable($insert)) {
                            $in = new AutoCompleteInsert($this->editor);
                            $insert($in);
                            $insert = $in->getValue();
                        }

                        $one = new CompletionItem();
                        $one->label = $item->getName();
                        $one->insertText = $insert;
                        $one->detail = $item->getDescription();
                        $one->documentation = $item->getDescription();
                        $one->kind = $this->getAutocompleteItemKind($item);
                        $one->insertAsSnippet = str::contains($insert, '$');
                        $result[] = $one;
                    }

                    return $result;
                } else {
                    return [];
                }
            }, function ($data) {
                return $data['item'];
            });
        });
    }

    public function setReadOnly($readOnly)
    {
        parent::setReadOnly($readOnly);
        $this->editor->getEditor()->readOnly = $readOnly;
    }

    /**
     * @return AutoComplete
     */
    public function getAutoComplete(): ?AutoComplete
    {
        return $this->autoComplete;
    }


    public function load()
    {
        // nope
    }

    public function save()
    {
        if (!$this->file) {
            return;
        }

        $value = $this->getValue();
        FileUtils::putAsync($this->file, $value)->then(function () {
            $this->fileTime = $this->file;
        });
    }

    public function requestFocus()
    {
        $this->editor->getEditor()->focus();
    }

    public function loadContentToArea()
    {
        if ($this->__content != null) {
            $this->editor->getEditor()->document->text = $this->__content;
        }
    }

    public function loadContentToAreaIfModified(): Promise
    {
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
    public function makeEditorUi()
    {
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
    public function setLanguage(string $language)
    {
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

    public function showFindDialog()
    {
        $this->editor->getEditor()->trigger('actions.find');
    }

    public function showReplaceDialog()
    {
        $this->editor->getEditor()->trigger('actions.find');
    }

    public function getAutocompleteString($onlyName = false)
    {
        $text = $this->editor->getEditor()->document->text;

        $i = $this->editor->getEditor()->getPositionOffset();

        if (!$onlyName) {
            return str::sub($text, 0, $i);
        }

        $string = '';

        while ($i-- >= 0) {
            $ch = $text[$i];

            if (Char::isPrintable($ch)
                && (Char::isLetterOrDigit($ch)) || $ch == '_') {
                $string .= $ch;
            } else {
                if ($onlyName /*&& $ch != '$'*/) { // todo refactor for $
                    break;
                } else {
                    $string .= $ch;
                }
            }
        }

        return str::reverse($string);
    }

    public function addAutocompleteType($name)
    {
        $type = $this->autoComplete->fetchType($name);

        if ($type) {
            $this->autoCompleteTypes[is_string($name) ? $name : str::uuid()] = $type;
        }
    }

    /**
     * @param $prefix
     * @param array $position
     * @return AutoCompleteItem[]
     */
    public function makeAutocompleteItems($prefix, array $position): array
    {
        $flow = Flow::ofEmpty();

        $region = $this->autoComplete->findRegion($position["lineNumber"], $position["column"]);

        foreach ($this->autoCompleteTypes as $type) {
            $flow = $flow
                ->append($type->getStatements($this->autoComplete, $region))
                ->append($type->getConstants($this->autoComplete, $region))
                ->append($type->getMethods($this->autoComplete, $region))
                ->append($type->getProperties($this->autoComplete, $region))
                ->append($type->getVariables($this->autoComplete, $region));
        }

        if ($prefix) {
            $flow = $flow->find(function (AutoCompleteItem $one) use ($prefix) {
                return Str::contains(str::lower($one->getName()), str::lower($prefix));
            });
        }

        $items = $flow->sort(function (AutoCompleteItem $one, AutoCompleteItem $two) use ($prefix) {
            $prefix = str::lower($prefix);
            $oneName = str::lower($one->getName());
            $twoName = str::lower($two->getName());

            if ($oneName == $twoName) {
                return 0;
            }

            if ($oneName == $prefix) {
                return -1;
            }
            if ($twoName == $prefix) {
                return 1;
            }

            if (str::startsWith($oneName, $prefix) && str::startsWith($twoName, $prefix)) {
                // nop.
            } else {
                if (str::startsWith($oneName, $prefix)) {
                    return -1;
                }

                if (str::startsWith($twoName, $prefix)) {
                    return 1;
                }
            }

            return Str::compare($oneName, $twoName);
        });

        if (arr::first($items) == $prefix && sizeof($items) < 2) {
            return [];
        }

        return $items;
    }

    public function getAutocompleteItemKind(AutoCompleteItem $item) {
        if ($item instanceof MethodAutoCompleteItem) {
            return 0;
        } else if ($item instanceof ConstantAutoCompleteItem) {
            return 14;
        } else if ($item instanceof VariableAutoCompleteItem) {
            return 4;
        } else if ($item instanceof PropertyAutoCompleteItem) {
            return 9;
        } else if ($item instanceof StatementAutoCompleteItem) {
            return 17;
        } else if ($item instanceof FunctionAutoCompleteItem) {
            return 1;
        } else {
            return 13;
        }
    }

    public function getAutocompleteHintString(): array
    {
        $text = $this->editor->getEditor()->document->text;

        $i = $this->editor->getEditor()->getPositionOffset();

        $braces = ['(' => 0, '[' => 0, '{' => 0];

        $hintMode = false;

        $endChar = $text[$i - 1];
        if (char::isSpace($endChar) || char::isPrintable($endChar) || str::contains('(,-+*/&|=%!~.<>"\'?^', $endChar)) {
            $hintMode = true;
        }

        if (!$hintMode) {
            return [];
        } else {
            // skip...
            $found = false;
            while ($i-- >= 0) {
                $ch = $text[$i];

                switch ($ch) {
                    case ")":
                        $braces['(']++;
                        break;
                    case "(":
                        $braces['(']--;
                        break;

                    case "]":
                        $braces['[']++;
                        break;
                    case "[":
                        $braces['[']--;
                        break;

                    case "}":
                        $braces['}']++;
                        break;
                    case "{":
                        $braces['{']--;
                        break;
                }

                if ($braces['('] < 0 && $braces['['] <= 0 && $braces['{'] === 0) {
                    $found = true;
                    break;
                }
            }

            if (!$found) return [];
        }

        return [$i, str::sub($text, 0, $i)];
    }
}
