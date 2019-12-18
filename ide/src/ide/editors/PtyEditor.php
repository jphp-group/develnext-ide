<?php

namespace ide\editors;

use ide\commands\ChangeThemeCommand;
use ide\Ide;
use php\gui\UXNode;
use php\intellij\pty\PtyProcess;
use php\intellij\ui\JediTermWidget;
use php\lang\System;

class PtyEditor extends AbstractEditor {

    /**
     * @var JediTermWidget
     */
    protected $terminal;

    /**
     * @var PtyProcess
     */
    protected $process;

    public function __construct($file) {
        parent::__construct($file);

        $args = ["cmd.exe"];
        $env  = System::getEnv();
        $dir  = System::getProperty("user.home");

        if (Ide::get()->isLinux() || Ide::get()->isMac()) {
            $args = ["/bin/bash", "--login"];
            $env["TERM"] = "xterm";
        }

        if (Ide::project())
            $dir = Ide::project()->getRootDir();

        $this->process = PtyProcess::exec($args, $env, $dir);

        $this->terminal = new JediTermWidget($this->process,
            ChangeThemeCommand::$instance->getCurrentTheme()->getTerminalTheme()->build());
        $this->terminal->requestFocus();
        $this->terminal->start();
    }

    public function getIcon() {
        return "icons/terminal16.png";
    }

    public function getTitle() {
        return _("editor.terminal.title");
    }

    public function load() {
        // nope
    }

    public function save() {
        // nope
    }

    public function isAutoClose() {
        return false;
    }

    public function close($save = true) {
        parent::close($save);

        if ($this->process->isAlive()) {
            $this->process->destroy();
            $this->terminal->stop();
        }
    }

    /**
     * @return UXNode
     */
    public function makeUi() {
        $node = $this->terminal->getFXNode();
        $node->on("click", function () use ($node) {
            uiLater(function () use ($node) { // fix for unix systems
                $node->requestFocus();
            });
        });

        return $node;
    }
}