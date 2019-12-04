<?php
namespace ide\forms;

use ide\commands\ChangeThemeCommand;
use ide\forms\mixins\SavableFormMixin;
use ide\Ide;
use ide\Logger;
use ide\project\ProjectConsoleOutput;
use ide\systems\FileSystem;
use php\gui\designer\UXCodeAreaScrollPane;
use php\gui\designer\UXRichTextArea;
use php\gui\event\UXEvent;
use php\gui\event\UXMouseEvent;
use php\gui\event\UXWindowEvent;
use php\gui\framework\AbstractForm;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\paint\UXColor;
use php\gui\text\UXFont;
use php\gui\UXApplication;
use php\gui\UXButton;
use php\gui\UXCheckbox;
use php\gui\UXDialog;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\UXListView;
use php\gui\UXNode;
use php\intellij\pty\PtyProcess;
use php\intellij\ui\JediTermWidget;
use php\io\IOException;
use php\io\Stream;
use php\lang\Process;
use php\lang\Thread;
use php\lang\ThreadPool;
use php\lib\char;
use php\lib\str;
use php\util\Regex;
use php\util\Scanner;
use php\util\SharedQueue;

/**
 * @property UXImageView $icon
 * @property UXListView $consoleList
 * @property UXCheckbox $closeAfterDoneCheckbox
 * @property UXButton $closeButton
 * @property UXNode $consoleArea
 * @property UXHBox $bottomPane
 * @property UXLabel $message
 *
 * Class BuildProgressForm
 * @package ide\forms
 */
class BuildProgressForm extends AbstractIdeForm implements ProjectConsoleOutput
{
    use SavableFormMixin;

    /**
     * @var PtyProcess
     */
    protected $process;

    /**
     * @var bool
     */
    protected $processDone = false;

    /** @var callable */
    protected $onExitProcess;

    /** @var callable */
    protected $stopProcedure;

    /** @var SharedQueue */
    protected $tasks;

    /**
     * @var JediTermWidget
     */
    protected $term;

    /**
     * @var bool
     */
    protected $ignoreExit1 = false;

    protected function init()
    {
        $this->icon->image = ico('wait32')->image;

        $this->consoleList->setCellFactory(function (UXListCell $cell, $item, $empty) {
            //$cell->font = UXFont::of('Courier New', 12);

            if (is_array($item)) {
                $cell->text = $item[0];
                $cell->textColor = UXColor::of($item[1]);
            }
        });


        $this->term = new JediTermWidget(null, ChangeThemeCommand::$instance->getCurrentTheme()->getTerminalTheme()->build());
        $this->consoleArea = $this->term->getFXNode();
        $this->consoleArea->on("click", function () {
            uiLater(function () {
                $this->term->requestFocus();
            });
        });

        $this->consoleArea->position = $this->consoleList->position;
        $this->consoleArea->size = $this->consoleList->size;
        $this->consoleArea->anchors = $this->consoleList->anchors;

        UXVBox::setVgrow($this->consoleArea, 'ALWAYS');

        $this->consoleList->parent->children->replace($this->consoleList, $this->consoleArea);

        $this->message->on('click', function () {
            $text = $this->message->text;

            $patterns = [
                "Uncaught\\ ([a-z\\_0-9]+)\\: (.+)\\ in\\ (.+)\\ on\\ line\\ ([0-9]+)\\,\\ position\\ ([0-9]+)",
                "'(.+)'\\ with\\ message\\ '(.+?)'\\ in\\ (.+\\.php)\\ on\\ line\\ ([0-9]+)\\,\\ position\\ ([0-9]+)"
            ];

            foreach ($patterns as $pattern) {
                $regex = new Regex($pattern, 'i', $text);
                $one = $regex->one();

                if ($one) {
                    list(, $type, $message, $file, $line, $position) = $one;

                    if (str::startsWith($file, 'res://')) {
                        $file = Ide::project()->getSrcFile(str::sub($file, 6));
                    }

                    $editor = FileSystem::open($file);

                    if ($editor) {
                        $editor->sendMessage(['error' => ['line' => $line, 'position' => $position, 'message' => $message]]);
                    }

                    return;
                }
            }
        });
    }

    /**
     * @return bool
     */
    public function isIgnoreExit1(): bool
    {
        return $this->ignoreExit1;
    }

    /**
     * @param bool $ignoreExit1
     */
    public function setIgnoreExit1(bool $ignoreExit1)
    {
        $this->ignoreExit1 = $ignoreExit1;
    }

    public function reduceHeader()
    {
        $this->content->padding = 5;
        $this->content->paddingBottom = 0;
        $this->content->spacing = 0;
        $this->header->spacing = 5;

        $this->workTitle->font = $this->workTitle->font->withSize(12)->withBold();
        $this->workDescription->free();
        $this->icon->preserveRatio = true;
        $this->icon->size = [16, 16];

        $this->header->free();
    }

    public function reduceFooter()
    {
        $this->bottomPane->height = $this->closeButton->height = 25;
        $this->closeButton->padding = [0, 8];
        $this->bottomPane->padding = 0;
        $this->bottomPane->spacing = 10;
        $this->bottomPane->paddingLeft = 0;
    }

    public function removeHeader()
    {
        $this->header->free();
    }

    public function removeProgressbar()
    {
        $this->progress->free();
    }

    /**
     * @param array $tasksOrProcesses
     */
    public function watchProcesses(array $tasksOrProcesses)
    {
        $tasks = new SharedQueue($tasksOrProcesses);

        $process = $tasks->poll();

        if ($process instanceof PtyProcess) {
            // nop
        } else if (is_callable($process)) {
            $process = $process();
        }

        $func = function ($exitCode) use ($tasks, &$func) {
            if ($exitCode == 0) {
                $process = $tasks->poll();

                if ($process instanceof PtyProcess) {
                    // nop.
                } else if (is_callable($process)) {
                    $process = $process();
                }

                if ($process) {
                    $this->watchProcess($process, $func);

                    return true;
                }
            }
        };

        $this->watchProcess($process, $func);
    }

    public function show(PtyProcess $process = null)
    {
        if ($process) {
            $this->watchProcess($process);
        }

        parent::show();
    }

    public function hide()
    {
        parent::hide();

        Ide::get()->setUserConfigValue('builder.closeAfterDone', $this->closeAfterDoneCheckbox->selected);
    }

    /**
     * @event closeAfterDoneCheckbox.click
     */
    public function doCloseAfterDoneCheckboxMouseDown()
    {
        uiLater(function () {
            Ide::get()->setUserConfigValue('builder.closeAfterDone', $this->closeAfterDoneCheckbox->selected);
        });
    }

    public function watchProcess(PtyProcess $process, callable $onExit = null)
    {
        $thread = new Thread(function () use ($process, $onExit) {
            $this->doProgress($process, $onExit);
        });
        $thread->setName('thread-build-process-' . str::random());
        $thread->start();
    }

    /**
     * @param callable $onExitProcess
     */
    public function setOnExitProcess($onExitProcess)
    {
        $this->onExitProcess = $onExitProcess;
    }

    /**
     * @param callable $stopProcedure
     */
    public function setStopProcedure($stopProcedure)
    {
        $this->stopProcedure = $stopProcedure;
    }

    /**
     * @event show
     */
    public function doOpen()
    {
        if ($this->progress) {
            $this->progress->progress = -1;
        }

        $this->closeAfterDoneCheckbox->selected = Ide::get()->getUserConfigValue('builder.closeAfterDone', true);
    }

    /**
     * @event close
     * @event closeButton.action
     *
     * @param UXEvent $e
     */
    public function doClose(UXEvent $e)
    {
        if (!$this->processDone) {
            if ($this->stopProcedure) {
                $stopProcedure = $this->stopProcedure;

                if (!$stopProcedure()) {
                    $e->consume();
                    return;
                }
            } else {
                UXDialog::show('Дождитесь сборки для закрытия прогресса.');
                $e->consume();

                return;
            }
        }

        $this->hide();
    }

    /**
     * @deprecated
     * @param $line
     * @param string $color
     */
    public function addConsoleLine($line, $color = '#333333') {

    }

    /**
     * @deprecated
     * @param $text
     * @param null $color
     */
    public function addConsoleText($text, $color = null) {

    }

    /**
     * @param \Exception $e
     */
    public function stopWithException(\Exception $e)
    {
        $this->processDone = true;

        if ($this->progress) {
            $this->progress->progress = 100;
        }
        //$this->closeButton->enabled = true;
    }

    public function stopWithError()
    {
        $this->processDone = true;

        if ($this->progress) {
            $this->progress->progress = 100;
        }
    }

    /**
     * @param Process $process
     * @param callable $onExit
     *
     */
    public function doProgress(PtyProcess $process, callable $onExit = null)
    {
        $this->term->createTerminalSession($process);
        $this->term->start();

        $process->waitFor();

        $exitValue = $process->getExitValue();
        $this->processDone = true;

        UXApplication::runLater(function() {
            if ($this->progress) {
                $this->progress->progress = 1;
            }
        });

        $func = function() use ($exitValue, $onExit) {
            if ($onExit) {
                $nextProcess = $onExit($exitValue, $exitValue != 0);

                if ($nextProcess) {
                    return;
                }
            }

            if ($this->closeAfterDoneCheckbox->selected) {
                $this->hide();
            }

            $onExitProcess = $this->onExitProcess;

            if ($onExitProcess) {
                $onExitProcess($exitValue, $exitValue != 0);

                Ide::get()->setUserConfigValue('builder.closeAfterDone', $this->closeAfterDoneCheckbox->selected);
            }
        };

        UXApplication::runLater($func);
    }
}