<?php
namespace ide\editors;

use ide\forms\CodeEditorSettingsForm;
use ide\forms\FindTextDialogForm;
use ide\forms\ReplaceTextDialogForm;
use ide\Logger;
use ide\misc\AbstractCommand;
use ide\misc\EventHandlerBehaviour;
use ide\systems\FileSystem;
use ide\utils\UiUtils;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\lang\IllegalArgumentException;
use php\lib\fs;

abstract class AbstractCodeEditor extends AbstractEditor
{
    use EventHandlerBehaviour;

    /**
     * @var AbstractCommand[]
     */
    protected array $commands = [];

    protected bool $embedded = false;
    protected bool $withSourceFile = false;

    protected FindTextDialogForm $findDialog;
    protected ReplaceTextDialogForm $replaceDialog;

    protected int $findDialogLastIndex = 0;

    protected UXHBox $statusBar;

    protected bool $contentLoaded = false;

    abstract public function getValue(): string;
    abstract public function setValue(string $value): void;
    abstract public function getSelectedText(): string;
    abstract public function undo();
    abstract public function redo();
    abstract public function copySelected();
    abstract public function cutSelected();
    abstract public function pasteFromClipboard();

    abstract public function jumpToLine(int $line, int $offset = 0);

    abstract public function makeEditorUi();

    public function makeUi()
    {
        if (!$this->isEmbedded()) {
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

        $ui->add($editorUi = $this->makeEditorUi());

        if ($this->isReadOnly()) {
            $ui->add($statusBar);
        }

        UXVBox::setVgrow($editorUi, 'ALWAYS');
        return $ui;
    }

    /**
     * @return bool
     */
    public function isWithSourceFile(): bool
    {
        return $this->withSourceFile;
    }

    /**
     * @param bool $withSourceFile
     */
    public function setWithSourceFile(bool $withSourceFile): void
    {
        $this->withSourceFile = $withSourceFile;
    }

    /**
     * @return bool
     */
    public function isEmbedded(): bool
    {
        return $this->embedded;
    }

    /**
     * @param bool $embedded
     */
    public function setEmbedded(bool $embedded): void
    {
        $this->embedded = $embedded;
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

    public function registerDefaultCommands()
    {
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

        /*$this->register(AbstractCommand::makeWithText('entity.settings::Настройки', 'icons/settings16.png', function () {
            $settingsForm = new CodeEditorSettingsForm();
            $settingsForm->setEditor($this);
            $settingsForm->showAndWait();
        }));*/
    }

    public function leave()
    {
        if (!$this->embedded) {
            $this->save();
        }
    }

    public function getFindDialog(): FindTextDialogForm
    {
        if ($this->findDialog) {
            return $this->findDialog;
        }

        return $this->findDialog = new FindTextDialogForm(function ($text, array $options) {
            $this->findSearchText($text, $options);
        });
    }

    public function getReplaceDialog(): ReplaceTextDialogForm
    {
        if ($this->replaceDialog) {
            return $this->replaceDialog;
        }

        return $this->replaceDialog = new ReplaceTextDialogForm(function ($text, $newText, array $options, $command) {
            $this->replaceSearchText($text, $newText, $options, $command);
        });
    }

    public function showFindDialog()
    {
        $this->findDialogLastIndex = 0;

        if ($this->getSelectedText()) {
            $this->getFindDialog()->setResult($this->getSelectedText());
        }

        $this->getFindDialog()->show();
    }

    public function showReplaceDialog()
    {
        $this->findDialogLastIndex = 0;

        if ($this->getSelectedText()) {
            $this->getReplaceDialog()->setResult($this->getSelectedText());
        }

        $this->getReplaceDialog()->show();
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
                    $this->undo();
                } catch (\Exception $e) {
                    Logger::warn("Undo fail: " . $e->getMessage());
                    // fix bug.
                }
                break;

            case 'redo':
                try {
                    $this->redo();
                } catch (\Exception $e) {
                    Logger::warn("Redo fail: " . $e->getMessage());
                    // fix bug.
                }
                break;
            case 'copy': $this->copySelected(); break;
            case 'cut': $this->cutSelected(); break;
            case 'paste': $this->pasteFromClipboard(); break;

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
}