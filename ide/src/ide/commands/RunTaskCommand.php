<?php

namespace ide\commands;

use function alert;
use function dump;
use function flow;
use framework\core\Promise;
use ide\editors\AbstractEditor;
use ide\forms\BuildProgressForm;
use ide\Ide;
use ide\Logger;
use ide\misc\AbstractCommand;
use ide\project\Project;
use ide\systems\FileSystem;
use ide\systems\ProjectSystem;
use php\gui\event\UXEvent;
use php\gui\layout\UXHBox;
use php\gui\UXComboBox;
use php\gui\UXContextMenu;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\UXMenuItem;
use php\gui\UXSplitMenuButton;
use php\intellij\pty\PtyProcess;
use php\lang\Process;
use const PHP_INT_MAX;
use function pre;
use process\ProcessHandle;
use function sizeof;
use function uiLater;
use function uiLaterAndWait;
use function var_dump;

class RunTaskCommand extends AbstractCommand
{
    private $taskSelect;
    private $stopButton;

    /**
     * @var UXHBox
     */
    private $panel;

    /**
     * @var UXContextMenu
     */
    private $menu;

    /**
     * RunTaskCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->stopButton = $this->makeGlyphButton();
        $this->stopButton->graphic = Ide::get()->getImage('icons/square16.png');
        $this->stopButton->tooltipText = 'command.stop';
        $this->stopButton->enabled = false;
        $this->stopButton = _($this->stopButton);


        $this->panel = $panel = new UXHBox([], 4);
        $this->taskSelect = $taskSelect = new UXSplitMenuButton('[не выбрано]', Ide::getImage($this->getIcon()));

        $taskSelect->maxHeight = PHP_INT_MAX;
        $taskSelect->minWidth = 120;

        /*$taskSelect->on('action', function () {
            uiLater(function () {
                $this->runButton->enabled = $this->taskSelect->value;
            });
        });*/

        $panel->add($taskSelect);
        $panel->add($this->stopButton);

        Ide::get()->bind('openProject', function (Project $project) {
            $project->getRunDebugManager()->on('change', function () use ($project) {
                $this->update($project);
            });

            $this->update($project);
        });

        Ide::get()->bind('closeProject', function (Project $project) {
            $project->getRunDebugManager()->off('change', __CLASS__);
            $this->update($project);
        });
    }

    public function update(?Project $project = null)
    {
        $project = $project ?: Ide::project();

        if ($project) {
            $items = $project->getRunDebugManager()->getItems();

            $this->taskSelect->items->clear();

            $i = 0;

            foreach ($items as $key => $item) {
                uiLaterAndWait(function () use ($key, $item, $items, $i) {
                    $menuItem = _(new UXMenuItem($item['title'] ?? $key));

                    $menuItem->graphic = Ide::getImage($item['icon'] ?? $this->getIcon());

                    $handler = function () use ($item, $key, $menuItem) {
                        $this->taskSelect->text = $item['title'] ?? $key;
                        $this->taskSelect = _($this->taskSelect);
                        $this->taskSelect->graphic = Ide::getImage($item['icon'] ?? $this->getIcon());

                        $this->taskSelect->on('action', function () use ($item) {
                            ProjectSystem::saveOnlyRequired();

                            $dialog = Ide::get()->getMainForm()->showCLI();

                            $stopFunc = $item['stopFunc'];
                            $this->stopButton->enabled = true;
                            $this->taskSelect->enabled = false;

                            $startProcess = function () use ($item, $dialog, $stopFunc) {
                                /** @var PtyProcess $process */
                                $processInfo = $item['makeStartProcess']();

                                $process = PtyProcess::exec($processInfo["args"], $processInfo["env"], $processInfo["dir"]);

                                $dialog->watchProcess($process);
                                $dialog->setStopProcedure(function () use ($stopFunc, $process) {
                                    $process->destroy();

                                    $this->taskSelect->enabled = true;
                                    $this->stopButton->enabled = false;
                                });

                                $dialog->setOnExitProcess(function () {
                                    $this->taskSelect->enabled = true;
                                    $this->stopButton->enabled = false;
                                });

                                $this->stopButton->on('action', function () use ($stopFunc, $process, $dialog) {
                                    $dialog->setIgnoreExit1(true);
                                    $process->destroy();

                                    $this->stopButton->enabled = false;
                                });
                            };

                            if ($prepareFunc = $item['prepareFunc']) {
                                $result = $prepareFunc($dialog);

                                if ($result instanceof Promise) {
                                    $result->then($startProcess);
                                } else {
                                    $startProcess();
                                }
                            } else {
                                $startProcess();
                            }
                        }, __CLASS__);
                    };
                    $menuItem->on('action', $handler);

                    if ($i === 0) $handler();

                    $this->taskSelect->items->add($menuItem);
                });
                $i++;
            }

            $this->panel->visible = $this->taskSelect->managed = sizeof($items) > 0;
        } else {
            $this->panel->visible = false;
            Logger::warn("Unable to update tasks, project is not opened.");
        }
    }

    protected function cellRender(): callable
    {
        return function (UXListCell $cell, $value) {
            $cell->graphic = Ide::getImage($this->getIcon());

            if (!$value) {
                $cell->text = '[не выбрано]';
                $cell->style = '-fx-text-fill: gray;';
            } else {
                $cell->text = $value;
                $cell->style = '';
            }
        };
    }

    protected function buttonRender(): callable
    {
        return function (UXListCell $cell, $value) {
            $cell->graphic = null;

            if (!$value) {
                $cell->text = '[не выбрано]';
                $cell->style = '-fx-text-fill: gray;';
            } else {
                $cell->graphic = Ide::getImage($this->getIcon());

                $cell->text = $value;
                $cell->style = '';
            }
        };
    }

    public function getName()
    {
        return 'command.start';
    }

    public function getAccelerator()
    {
        return 'F9';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $this->taskSelect->trigger('action', UXEvent::makeMock($this->taskSelect));
    }

    public function getIcon()
    {
        return 'icons/run16.png';
    }

    public function isAlways()
    {
        return true;
    }

    public function makeUiForHead()
    {
        $this->update();
        return $this->panel;
    }

    public function makeMenuItem()
    {
        return null;
    }
}