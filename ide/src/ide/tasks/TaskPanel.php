<?php

namespace ide\tasks;

use ide\commands\ChangeThemeCommand;
use ide\Ide;
use ide\ui\elements\DNAnchorPane;
use ide\ui\elements\DNButton;
use ide\ui\elements\DNCheckbox;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\UXNode;
use php\intellij\tty\PtyProcess;
use php\intellij\tty\PtyProcessConnector;
use php\intellij\ui\JediTermWidget;
use php\lib\str;

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

    /**
     * @var boolean
     */
    private $configurationProcessExitTriggered = false;

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

        $this->terminal = new JediTermWidget(ChangeThemeCommand::$instance->getCurrentTheme()->getTerminalTheme()->build());
        $this->terminal->createTerminalSession(new PtyProcessConnector($this->process));
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

        $panel = new DNAnchorPane();
        $panel->add($node);

        $destroyButton = _(new DNButton("command.close", ico("525:square,16px,#d04949")));
        $destroyButton->on("action", function () use ($destroyButton) {
            if ($this->process->isAlive())
                $this->destroy();

            $this->closeAfterExit = true;
            $this->triggerDestroyEvent();
        });

        $hideCheckbox = _(new DNCheckbox("command.close.after.exit"));
        $hideCheckbox->selected = $this->closeAfterExit;
        $hideCheckbox->on("action", function () use ($hideCheckbox) {
            Ide::get()->setUserConfigValue("builder.closeAfterDone", $this->closeAfterExit = $hideCheckbox->selected);
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
            $this->terminal->nextLine();
            $this->terminal->nextLine();
            $this->terminal->writeString( str::replace(((string)_("process.exit.message")), "%n", $this->process->getExitValue()));

            call_user_func($this->onProcessExit, $this->process->getExitValue());

            if (!$this->configurationProcessExitTriggered) {
                $this->configuration->onProcessExit($this->process->getExitValue());
                $this->configurationProcessExitTriggered = true;
            }
        });
    }

    /**
     * @return bool
     */
    public function isCloseAfterExit(): bool {
        return $this->closeAfterExit;
    }
}
