<?php

namespace ide\editors;

use ide\Ide;
use php\gui\UXDialog;
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
        $this->terminal = new JediTermWidget();
        $this->terminal->createTerminalSession($this->process);
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
            if (UXDialog::confirm(_("editor.terminal.exit.message"))) {
                $this->process->destroy();
                $this->terminal->stop();
            }
        }
    }

    /**
     * @return UXNode
     */
    public function makeUi() {
        return $this->terminal->getFXNode();
    }
}