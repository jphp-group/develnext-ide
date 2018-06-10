<?php

namespace ide\commands;

use function alert;
use function dump;
use function flow;
use ide\editors\AbstractEditor;
use ide\forms\BuildProgressForm;
use ide\Ide;
use ide\misc\AbstractCommand;
use ide\project\Project;
use ide\systems\FileSystem;
use ide\systems\ProjectSystem;
use php\gui\layout\UXHBox;
use php\gui\UXComboBox;
use php\gui\UXContextMenu;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\UXMenuItem;
use php\gui\UXSplitMenuButton;
use php\lang\Process;
use const PHP_INT_MAX;
use function uiLater;
use function var_dump;

class RunTaskCommand extends AbstractCommand
{
    private $taskSelect;
    private $runButton;
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

        Ide::get()->on('openProject', function (Project $project) {
            $project->getRunDebugManager()->on('change', function () {
                $this->update();
            });

            $this->update();
        });

        Ide::get()->on('closeProject', function (Project $project) {
            $project->getRunDebugManager()->off('change', __CLASS__);
            $this->update();
        });
    }

    public function update()
    {
        if ($project = Ide::project()) {
            $items = $project->getRunDebugManager()->getItems();

            $this->taskSelect->items->clear();

            $i = 0;
            foreach ($items as $key => $item) {
                uiLater(function () use ($key, $item, $items, $i) {
                    $menuItem = new UXMenuItem($item['title'] ?? $key);

                    $menuItem->graphic = Ide::getImage($this->getIcon());
                    $handler = function () use ($item, $key, $menuItem) {
                        $this->taskSelect->text = $menuItem->text;

                        $this->taskSelect->on('action', function () use ($item) {
                            ProjectSystem::saveOnlyRequired();

                            /** @var Process $process */
                            $process = $item['makeStartProcess']();
                            $process = $process->start();

                            $dialog = Ide::get()->getMainForm()->showCLI();
                            $dialog->watchProcess($process);
                        }, __CLASS__);
                    };
                    $menuItem->on('action', $handler);

                    if ($i === 0) $handler();

                    $this->taskSelect->items->add($menuItem);
                });
                $i++;
            }

            $this->taskSelect->visible = $this->taskSelect->managed = $this->taskSelect->items->count() > 0;
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
        return 'Пуск';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
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