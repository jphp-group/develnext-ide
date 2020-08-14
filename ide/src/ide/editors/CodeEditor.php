<?php
namespace ide\editors;

use develnext\lexer\inspector\AbstractInspector;
use ide\autocomplete\AutoComplete;
use ide\autocomplete\ui\AutoCompletePane;
use ide\forms\AbstractIdeForm;
use ide\forms\CodeEditorSettingsForm;
use ide\forms\FindTextDialogForm;
use ide\forms\MessageBoxForm;
use ide\forms\ReplaceTextDialogForm;
use ide\Ide;
use ide\Logger;
use ide\misc\AbstractCommand;
use ide\misc\EventHandlerBehaviour;
use ide\systems\FileSystem;
use ide\ui\elements\DNCheckbox;
use ide\utils\FileUtils;
use ide\utils\UiUtils;
use php\concurrent\Promise;
use php\gui\UXGenericStyledArea;
use function is_array;
use php\gui\designer\UXAbstractCodeArea;
use php\gui\designer\UXCodeAreaScrollPane;
use php\gui\designer\UXPhpCodeArea;
use php\gui\designer\UXSyntaxTextArea;
use php\gui\designer\UXTextCodeArea;
use php\gui\event\UXKeyEvent;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXApplication;
use php\gui\UXCheckbox;
use php\gui\UXDialog;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\gui\UXTooltip;
use php\io\File;
use php\io\IOException;
use php\lang\IllegalArgumentException;
use php\lib\fs;
use php\lib\Str;

/**
 * Class CodeEditor
 * @package ide\editors
 */
class CodeEditor extends AbstractEditor
{
    use EventHandlerBehaviour;

    protected $mode;

    /**
     * @var UXVBox
     */
    protected $ui;

    /**
     * @var AbstractCommand[]
     */
    protected $commands = [];

    /**
     * @var UXSyntaxTextArea|UXAbstractCodeArea
     */
    protected $textArea;

    /**
     * @var UXCodeAreaScrollPane
     */
    protected $textAreaScrollPane;

    /**
     * @var AutoCompletePane
     */
    protected $autoComplete;

    /**
     * @var FindTextDialogForm
     */
    protected $findDialog;

    /**
     * @var ReplaceTextDialogForm
     */
    protected $replaceDialog;

    /**
     * @var bool
     */
    protected $sourceFile = false;

    /**
     * @var int
     */
    protected $findDialogLastIndex = 0;

    /**
     * @var bool
     */
    protected $contentLoaded = false;

    /**
     * @var UXHBox
     */
    protected $statusBar;

    /**
     * @var bool
     */
    protected $embedded = false;

    /**
     * @return UXAbstractCodeArea|UXSyntaxTextArea
     */
    public function getTextArea()
    {
        return $this->textArea;
    }

    /**
     * @return boolean
     */
    public function isSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @param boolean $sourceFile
     */
    public function setSourceFile($sourceFile)
    {
        $this->sourceFile = $sourceFile;
    }

    /**
     * CodeEditor constructor.
     * @param string $file
     * @param string $mode
     * @param array $options
     */
    public function __construct($file, $mode = 'php', $options = array())
    {
        parent::__construct($file);

        $this->mode = $mode;

        $textArea = $options['textArea'] instanceof UXAbstractCodeArea ? $options['textArea'] : null;

        if ($textArea === null) {
            switch ($mode) {
                case 'php':
                    $this->textArea = new UXPhpCodeArea();
                    break;

                default:
                    $this->textArea = new UXTextCodeArea();
                    break;
            }
        } else {
            $this->textArea = $textArea;
        }

        $this->textArea->on('keyUp', function (UXKeyEvent $e) {
            if ($e->controlDown) {
                switch ($e->codeName) {
                    case 'F':
                        $this->executeCommand('find');
                        $e->consume();
                        return;
                    case 'R':
                        $this->executeCommand('replace');
                        $e->consume();
                        return;
                }
            }

            $this->doChange();
        });

        if ($options['autoComplete'] instanceof AutoComplete) {
            $this->autoComplete = new AutoCompletePane($this->textArea, $options['autoComplete']);
        } else if (is_array($options['autoComplete'])) {
            if ($project = Ide::project()) {
                $bindId = str::random();

                if ($inspector = $project->getInspector($options['autoComplete']['context'])) {
                    $class = $options['autoComplete']['class'];
                    $this->autoComplete = new AutoCompletePane($this->textArea, new $class($inspector));
                } else {
                    $project->on('registerInspector', function ($context, AbstractInspector $inspector) use ($options, $bindId, $project) {
                        if ($context === $options['autoComplete']['context']) {
                            $class = $options['autoComplete']['class'];
                            $this->autoComplete = new AutoCompletePane($this->textArea, new $class($inspector));
                            $project->off('registerInspector', $bindId);
                        }

                    }, $bindId);
                }
            }
        }

        $this->resetSettings();
    }

    /**
     * @return AutoCompletePane
     */
    public function getAutoComplete()
    {
        return $this->autoComplete;
    }

    public function close($save = true)
    {
        parent::close($save);
    }

    public function leave()
    {
        if (!$this->embedded) {
            $this->save();
        }
    }

    /**
     * @return bool
     */
    public function isEmbedded()
    {
        return $this->embedded;
    }

    /**
     * @param bool $embedded
     */
    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;
    }

    public function getFindDialog()
    {
        if ($this->findDialog) {
            return $this->findDialog;
        }

        return $this->findDialog = new FindTextDialogForm(function ($text, array $options) {
            $this->findSearchText($text, $options);
        });
    }

    public function getReplaceDialog()
    {
        if ($this->replaceDialog) {
            return $this->replaceDialog;
        }

        return $this->replaceDialog = new ReplaceTextDialogForm(function ($text, $newText, array $options, $command) {
            $this->replaceSearchText($text, $newText, $options, $command);
        });
    }

    public function setReadOnly($value)
    {
        parent::setReadOnly($value);

        if ($this->textArea) {
            $this->textArea->editable = !$value;
        }

        if ($this->statusBar) {
            if ($value) {
                $this->statusBar->free();
            } else {
                $this->ui->add($this->statusBar);
            }
        }
    }

    /**
     * @param $any
     *
     * @throws IllegalArgumentException
     */
    public function register($any)
    {
        if ($any instanceof AbstractCommand) {
            $any->setTarget($this);
            $this->commands[] = $any;
        } else {
            throw new IllegalArgumentException();
        }
    }

    public $__eventUpdates = 0;

    /**
     * Trigger change content.
     * @param bool $now
     */
    public function doChange($now = false)
    {
        if ($now) {
            $this->trigger('update', []);
        } else {
            $i = ++$this->__eventUpdates;

            waitAsync(1000, function () use ($i) {
                if ($i == $this->__eventUpdates) {
                    $this->trigger('update', []);
                }
            });
        }
    }

    public function executeCommand($command)
    {
        switch ($command) {
            case 'undo':
                try {
                    $this->textArea->undo();
                } catch (\Exception $e) {
                    Logger::warn("Undo fail: " . $e->getMessage());
                    // fix bug.
                }
                break;

            case 'redo':
                try {
                    $this->textArea->redo();
                } catch (\Exception $e) {
                    Logger::warn("Redo fail: " . $e->getMessage());
                    // fix bug.
                }
                break;
            case 'copy': $this->textArea->copy(); break;
            case 'cut': $this->textArea->cut(); break;
            case 'paste': $this->textArea->paste(); break;

            case 'find':
                $this->showFindDialog();
                break;

            case 'replace':
                $this->showReplaceDialog();
                break;

            default:
                ;
        }
    }

    public function getTitle()
    {
        return $this->format->getTitle($this->file);
    }

    public function jumpToLine($line, $offset = 0)
    {
        $this->textArea->requestFocus();
        $this->textArea->jumpToLine($line, $offset);
    }

    public function getValue()
    {
        return $this->textArea->text;
    }

    public function setValue($value)
    {
        $value = str::replace($value, "\t", str::repeat(" ", 4));

        if ($this->textArea instanceof UXGenericStyledArea) {
            $this->textArea->replaceText(0, str::length($this->textArea->text), $value);
        } else {
            $this->textArea->text = $value;
        }
    }

    public function loadContentToAreaIfModified(callable $callback = null)
    {
        if (!$this->contentLoaded || $this->fileTime != fs::time($this->file)) {
            $resetHistory = !$this->contentLoaded;
            $this->loadContentToArea($resetHistory, $callback);
        } else {
            if ($callback)
                $callback();
        }
    }

    public function open($param = null)
    {
        parent::open($param);

        $this->resetSettings();

        if (!$this->file) {
            $this->loadContentToArea();
        } else {
            if (!$this->embedded) {
                $this->requestFocus();
            }

            $this->loadContentToAreaIfModified();
        }

        if (!$this->embedded && !$this->isTabbed()) {
            $this->on('update', function () {
                $this->save();
            }, __CLASS__);

            $this->textArea->on('keyDown', function (UXKeyEvent $e) {
                if ($e->controlDown && $e->codeName == 'S') {
                    $this->save();
                }
            }, __CLASS__);
        }
    }

    public function loadContentToArea($resetHistory = true, callable $callback = null)
    {
        $this->contentLoaded = true;

        if (!$this->file) {
            return;
        }

        $caret = $this->textArea->caretPosition;

        if ($this->textAreaScrollPane) {
            list($sX, $sY) = [$this->textAreaScrollPane->scrollX, $this->textAreaScrollPane->scrollY];
        }

        $file = $this->file;

        Logger::info("Start load file $file");

        $func = function ($content) use ($resetHistory, $caret, $sX, $sY, $file, $callback, $promise) {
            $this->setValue($content);

            if ($resetHistory) {
                if (!($this->textArea instanceof UXGenericStyledArea)) {
                    $this->textArea->forgetHistory();
                }
            }

            $this->textArea->caretPosition = $caret;

            if ($this->textAreaScrollPane) {
                $this->textAreaScrollPane->scrollX = $sX;
                $this->textAreaScrollPane->scrollY = $sY;
            }

            Logger::info("Finish load file $file");

            $this->fileTime = fs::time($this->file);

            if ($callback) {
                $callback();
            }
        };

        $promise = new Promise(function (callable $resolve, callable $reject) use ($file, $func) {
            FileUtils::getAsync($file)->then(function ($result) use ($func, $resolve){
                $func($result);
                $resolve($result);
            })->catch($reject);
        });

        return $promise;
    }

    public function load($resetHistory = true)
    {
        if (!$this->file) {
            return;
        }
    }

    public function save()
    {
        if ($this->readOnly) {
            return;
        }

        Logger::info("Start save file $this->file ...");

        if (!$this->contentLoaded) {
            Logger::warn("File '$this->file' cannot be saved, is not loaded to area.");
            return;
        }

        if (!$this->file) {
            return;
        }

        $value = $this->getValue();

        if (!fs::exists($this->file)) {
            FileUtils::putAsync($this->file, $value);
        }

        if ($this->isSourceFile()) {
            FileUtils::putAsync("$this->file.source", $value);
        } else {
            FileUtils::putAsync($this->file, $value);
        }

        Logger::info("Finish save file $this->file.");
        $this->fileTime = fs::time($this->file);
    }

    /**
     * @return UXNode
     * @throws IllegalArgumentException
     */
    public function makeUi()
    {
        if (!$this->embedded) {
            $this->registerDefaultCommands();
        }

        $this->ui = $ui = new UXVBox();

        $commandPane = UiUtils::makeCommandPane($this->commands);
        $commandPane->padding = 5;
        $commandPane->spacing = 4;
        $commandPane->fillHeight = true;

        if ($this->commands) {
            $ui->add($commandPane);
        }

        $this->statusBar = $statusBar = new UXHBox();
        $label = new UXLabel("* Только для чтения");
        $label->font = $label->font->withBold();
        $label->textColor = 'red';
        $statusBar->backgroundColor = 'white';

        $statusBar->add($label);
        $statusBar->padding = 5;

        $this->textAreaScrollPane = $scrollPane = new UXCodeAreaScrollPane($this->textArea);
        $ui->add($scrollPane);

        if ($this->isReadOnly()) {
            $ui->add($statusBar);
        }

        UXVBox::setVgrow($scrollPane, 'ALWAYS');

        $resize = function () {
            $this->refreshUi();
        };

        $ui->observer('height')->addListener($resize);
        $ui->observer('width')->addListener($resize);

        return $ui;
    }

    public function refreshUi()
    {
        $ui = $this->ui;

        $ui->requestLayout();

        waitAsync(500, function () use ($ui) {
            $ui->requestLayout();
        });

        waitAsync(1000, function () use ($ui) {
            $ui->requestLayout();
        });
    }

    public function makeLeftPaneUi()
    {
        return null;
    }

    public function registerDefaultCommands()
    {
        if (!$this->embedded) {
            if (!$this->embedded) {
                if ($this->isTabbed()) {
                    $this->register(AbstractCommand::make('editor.in.window::В отдельном окне', 'icons/tabRight16.png', function () {
                        $this->save();

                        FileSystem::close($this->file);
                        FileSystem::open($this->file, true, null, true);
                    }));
                } else {
                    $this->register(AbstractCommand::make('editor.in.tab::В виде таба', 'icons/tab16.png', function () {
                        $this->save();

                        FileSystem::close($this->file);
                        FileSystem::open($this->file);
                    }));
                }
            }

            if (!$this->isTabbed()) {
                $this->register(AbstractCommand::make('code.editor.command.save::Сохранить (Ctrl + S)', 'icons/save16.png', function () {
                    $this->save();
                }));
            }

            $this->register(AbstractCommand::makeSeparator());
        }

        $this->register(AbstractCommand::make('code.editor.command.undo::Отменить (Ctrl + Z)', 'icons/undo16.png', function () {
            $this->executeCommand('undo');
        }));

        $this->register(AbstractCommand::make('code.editor.command.redo::Вернуть (Ctrl + Shift + Z)', 'icons/redo16.png', function () {
            $this->executeCommand('redo');
        }));

        $this->register(AbstractCommand::makeSeparator());

        $this->register(AbstractCommand::make('code.editor.command.cut::Вырезать (Ctrl + X)', 'icons/cut16.png', function () {
            $this->executeCommand('cut');
        }));

        $this->register(AbstractCommand::make('code.editor.command.copy::Копировать (Ctrl + C)', 'icons/copy16.png', function () {
            $this->executeCommand('copy');
        }));

        $this->register(AbstractCommand::make('code.editor.command.paste::Вставить (Ctrl + V)', 'icons/paste16.png', function () {
            $this->executeCommand('paste');
        }));

        $this->register(AbstractCommand::makeSeparator());


        $this->register(AbstractCommand::makeWithText('command.find::Найти', 'icons/search16.png', function () {
            $this->executeCommand('find');
        }));

        $this->register(AbstractCommand::makeWithText('command.replace::Заменить', 'icons/replace16.png', function () {
            $this->executeCommand('replace');
            $this->save();
        }));

        $this->register(AbstractCommand::makeSeparator());

        $this->register(AbstractCommand::makeWithText('entity.settings::Настройки', 'icons/settings16.png', function () {
            $settingsForm = new CodeEditorSettingsForm();
            $settingsForm->setEditor($this);
            $settingsForm->showAndWait();
        }));
    }

    public function requestFocus()
    {
        $this->textArea->requestFocus();
    }

    protected function _findSearchText(AbstractIdeForm $dialog, $text, array $options, $silent = false)
    {
        $len = $pos = 0;

        Logger::debug("Find search text '$text', from {$this->findDialogLastIndex}");

        $case = $options['case'];
        $method = str::class . '::' . ($case ? 'pos' : 'posIgnoreCase');

        if ($options['wholeText']) {
            $words = [$text];
        } else {
            $words = str::split($text, ' ');
        }

        foreach ($words as $word) {
            $pos = $method($this->textArea->text, $word, $this->findDialogLastIndex);
            $len = str::length($word);

            if ($pos > -1) {
                break;
            }
        }

        if ($pos == -1) {
            if ($this->findDialogLastIndex == 0) {
                if (!$silent) {
                    UXDialog::showAndWait(_('message.nothing.found::Ничего не найдено.'));
                    $dialog->show();
                }

                return null;
            }

            if (!$silent && MessageBoxForm::confirm('message.confirm.nothing.found.repeat::Больше ничего не найдено, начать сначала?', $this->textArea)) {
                $this->findDialogLastIndex = 0;
                $dialog->show();
                $this->_findSearchText($dialog, $text, $options);
            }

            return null;
        }

        $this->findDialogLastIndex = $pos + 1;
        $this->textArea->caretPosition = $pos;
        $this->textArea->select($pos, $pos + $len);

        Logger::debug("Find select [$pos, $len]");

        return [$pos, $len];
    }

    protected function findSearchText($text, array $options)
    {
        return $this->_findSearchText($this->getFindDialog(), $text, $options);
    }

    protected function replaceSearchText($text, $newText, $options, $command)
    {
        Logger::debug("Replace search text '$text', from {$this->findDialogLastIndex}");

        $result = null;

        switch ($command) {
            case 'START':
                $this->findDialogLastIndex = 0;
            // continue.

            case 'SKIP':
                $result = $this->_findSearchText($this->getReplaceDialog(), $text, $options, true);
                break;

            case 'REPLACE_ALL':
                if (MessageBoxForm::confirm('message.confirm.replace.all::Вы уверены, что хотите заменить все?')) {
                    $result = $this->_findSearchText($this->getReplaceDialog(), $text, $options, true);

                    if (!$result) {
                        UXDialog::showAndWait(_('message.nothing.found::Ничего не найдено.'));
                        break;
                    }

                    $pos = $this->textArea->caretPosition;
                    $scrollX = $this->textAreaScrollPane->scrollX;
                    $scrollY = $this->textAreaScrollPane->scrollY;

                    if ($options['case']) {
                        $this->textArea->text = str::replace($this->textArea->text, $text, $newText);
                    } else {
                        $this->textArea->text = str_ireplace($text, $newText, $this->textArea->text);
                    }

                    $this->textArea->caretPosition = $pos;
                    $this->textAreaScrollPane->scrollX = $scrollX;
                    $this->textAreaScrollPane->scrollY = $scrollY;
                } else {
                    $this->getReplaceDialog()->setResult($text);
                    $this->showReplaceDialog();
                }
                break;

            case 'REPLACE':
                if (!$this->textArea->selectedText) {
                    UXDialog::showAndWait('message.nothing.found::Ничего не найдено.');
                    break;
                }

                $this->textArea->selectedText = $newText;
                $end = $this->findDialogLastIndex - 1 + str::length($newText);

                $this->textArea->select($this->findDialogLastIndex - 1, $end);
                $this->findDialogLastIndex = $end + 1;

                $result = $this->_findSearchText($this->getReplaceDialog(), $text, $options, true);

                if (!$result) {
                    $this->textArea->select(0, 0);
                }

                break;
        }

        $this->getReplaceDialog()->setFindResult($result);
    }

    public function showFindDialog()
    {
        $this->findDialogLastIndex = 0;

        if ($this->textArea->selectedText) {
            $this->getFindDialog()->setResult($this->textArea->selectedText);
        }

        $this->getFindDialog()->show();
    }

    public function showReplaceDialog()
    {
        $this->findDialogLastIndex = 0;

        if ($this->textArea->selectedText) {
            $this->getReplaceDialog()->setResult($this->textArea->selectedText);
        }

        $this->getReplaceDialog()->show();
    }

    public function jumpToLineSpaceOffset($beginLine)
    {
        $this->textArea->jumpToLineSpaceOffset($beginLine);

        waitAsync(250, function () use ($beginLine) {
            //$this->textArea->estimatedScrollY = ($this->textArea->lineHeight) * $beginLine;
            $this->textArea->moveTo($beginLine);
        });
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return array|null
     */
    public function getCaretBounds()
    {
        return $this->textArea ? $this->textArea->caretBounds : null;
    }

    public function sendMessage($message)
    {
        $this->jumpToLineSpaceOffset($message['error']['line'] - 1);

        if ($message['error']['message']) {
            $tooltip = new UXTooltip();
            $tooltip->text = $message['error']['message'];
            $tooltip->graphic = ico('error16');

            $bounds = $this->getCaretBounds();

            $tooltip->show($this->ui->window, $bounds['x'], $bounds['y'] + 25);

            $handler = function () use ($tooltip) {
                $tooltip->hide();
            };
            waitAsync(5000, $handler);
            $tooltip->on('click', $handler);
        }
    }


    /**
     * Reset default settings.
     */
    public function resetSettings()
    {
        $this->setHighlight(self::getCurrentHighlight($this->mode));
        $this->setFontSize(self::getCurrentFontSize($this->mode));
    }

    /**
     * @param string $name
     */
    public function setHighlight($name)
    {
        $file = self::getHighlightFile($this->mode, $name);

        if ($file->isFile()) {
            $this->textArea->setStylesheet(FileUtils::urlPath($file));
        }
    }

    /**
     * @param int $size
     */
    public function setFontSize($size)
    {
        $this->textArea->style = "-fx-font-size: {$size}px";
    }

    /**
     * @param $lang
     * @param $size
     */
    public static function setCurrentFontSize($lang, $size)
    {
        Ide::get()->setUserConfigValue(__CLASS__ . '#' . $lang . '.fontSize', $size);
    }

    /**
     * @return int
     */
    public static function getCurrentFontSize($lang)
    {
        $value = (float) Ide::get()->getUserConfigValue(__CLASS__ . '#' . $lang . '.fontSize', 14.5);

        if ($value < 8) $value = 8;
        if ($value > 20) $value = 20;

        return $value;
    }

    /**
     * @param string $lang
     * @return \php\io\File[]
     */
    public static function getHighlightFiles($lang)
    {
        $dir = Ide::getOwnFile('highlights');

        if (Ide::get()->isDevelopment() && fs::isDir(Ide::getOwnFile('misc/highlights'))) {
            $dir = Ide::getOwnFile('misc/highlights');
        }

        $dir = "$dir/$lang";

        return File::of($dir)->findFiles(function (File $directory, $name) {
            return fs::ext($name) == 'css';
        });
    }

    /**
     * @param $lang
     * @param $name
     * @return File
     */
    public static function getHighlightFile($lang, $name)
    {
        $dir = Ide::getOwnFile('highlights');

        if (Ide::get()->isDevelopment() && fs::isDir(Ide::getOwnFile('misc/highlights'))) {
            $dir = Ide::getOwnFile('misc/highlights');
        }

        return File::of("$dir/$lang/$name.css");
    }

    /**
     * @param string $lang
     * @return string
     */
    public static function getCurrentHighlight($lang)
    {
        $value = Ide::get()->getUserConfigValue(__CLASS__ . '#' . $lang . '.highlight', 'DevelNext-Dark');

        if (!self::getHighlightFile($lang, $value)->isFile()) {
            return 'DevelNext-Dark';
        }

        return $value;
    }

    /**
     * @param $lang
     * @param $value
     */
    public static function setCurrentHighlight($lang, $value)
    {
        Ide::get()->setUserConfigValue(__CLASS__ . '#' . $lang . '.highlight', $value);
    }
}

class SetDefaultCommand extends AbstractCommand
{
    /**
     * @var FormEditor
     */
    protected $formEditor;

    protected $editor;

    /**
     * SetDefaultCommand constructor.
     * @param FormEditor $formEditor
     * @param $editor
     */
    public function __construct(FormEditor $formEditor, $editor)
    {
        $this->formEditor = $formEditor;
        $this->editor = $editor;
    }

    public function getName()
    {
        return 'editor.use.by.default::Использовать по умолчанию';
    }

    public function makeUiForHead()
    {
        $ui = new DNCheckbox($this->getName());
        $ui->padding = 3;

        $ui->selected = $this->formEditor->getDefaultEventEditor(false) == "php";

        UXApplication::runLater(function () use ($ui) {
            $ui->watch('selected', function (UXNode $self, $property, $oldValue, $newValue) {
                if ($newValue) {
                    $this->formEditor->setDefaultEventEditor($this->editor);
                } else {
                    $this->formEditor->setDefaultEventEditor($this->editor == 'php' ? 'constructor' : 'php');
                }
            });
        });

        return $ui;
    }

    public function withBeforeSeparator()
    {
        return true;
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        //
    }
}
