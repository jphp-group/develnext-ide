<?php

namespace ide\tasks;

use ide\commands\ChangeThemeCommand;
use ide\Ide;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\UXButton;
use php\gui\UXCheckbox;
use php\gui\UXNode;
use php\intellij\pty\PtyProcess;
use php\intellij\ui\JediTermWidget;

class TaskPanel {
    /**
     * @var TaskProcessInfo
     */
    private $processInfo;

    /**
     * @var AbstractTaskConfiguration
     */
    private $configuration;

    /**
     * @var callable
     */
    private $onProcessExit;

    /**
     * @var JediTermWidget
     */
    private $terminal;

    /**
     * @var PtyProcess
     */
    private $process;

    /**
     * @var boolean
     */
    private $closeAfterExit;

    public function __construct(AbstractTaskConfiguration $configuration) {
        $this->processInfo = $configuration->getTaskInfo();
        $this->configuration = $configuration;

        $this->closeAfterExit = Ide::get()->getUserConfigValue('builder.closeAfterDone', false);
    }

    public function setOnProcessExit(callable $callback) {
        $this->onProcessExit = $callback;
    }

    public function makeUI(): UXNode {
        $this->process = PtyProcess::exec($this->processInfo->getProgram(), $this->processInfo->getEnvironment(), $this->processInfo->getDirectory());

        $this->terminal = new JediTermWidget($this->process,
            ChangeThemeCommand::$instance->getCurrentTheme()->getTerminalTheme()->build());
        $this->terminal->requestFocus();
        $this->terminal->start();

        $node = $this->terminal->getFXNode();
        $node->on("click", function () use ($node) {
            uiLater(function () use ($node) { // fix for unix systems
                $node->requestFocus();
            });
        });

        Ide::async(function () {
            while ($this->process->isAlive());
            $this->triggerDestroyEvent();
        });

        $panel = new UXAnchorPane();
        $panel->add($node);

        $destroyButton = _(new UXButton("command.close", Ide::getImage("icons/square16.png")));
        $destroyButton->on("action", function () use ($destroyButton) {
            if ($this->process->isAlive())
                $this->destroy();
            else {
                $this->closeAfterExit = true;
                $this->triggerDestroyEvent();
            }
        });

        $hideCheckbox = _(new UXCheckbox("command.close.after.exit"));
        $hideCheckbox->selected = $this->closeAfterExit;
        $hideCheckbox->on("action", function () use ($hideCheckbox) {
            $this->closeAfterExit = $hideCheckbox->selected;

            Ide::get()->setUserConfigValue("builder.closeAfterDone", $this->closeAfterExit);
        });

        $box = new UXHBox([ $destroyButton, $hideCheckbox ], 8);
        $box->alignment = "CENTER_LEFT";
        $panel->add($box);

        UXAnchorPane::setAnchor($node, 8);
        UXAnchorPane::setBottomAnchor($node, 40);
        UXAnchorPane::setBottomAnchor($box, 8);
        UXAnchorPane::setLeftAnchor($box, 8);

        return $panel;
    }

    public function destroy() {
        if ($this->process->isAlive()) {
            $this->process->destroy();
            $this->terminal->stop();
        }
    }

    private function triggerDestroyEvent() {
        uiLater(function () {
            call_user_func($this->onProcessExit, $this->process->getExitValue());
            $this->configuration->onProcessExit($this->process->getExitValue());
        });
    }

    /**
     * @return bool
     */
    public function isCloseAfterExit(): bool {
        return $this->closeAfterExit;
    }
}
