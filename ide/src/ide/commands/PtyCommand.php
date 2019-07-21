<?php

namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\misc\AbstractCommand;
use ide\systems\FileSystem;

class PtyCommand extends AbstractCommand {

    public function getName() {
        return _("editor.terminal.title");
    }

    public function getIcon() {
        return "icons/terminal16.png";
    }

    public function getCategory() {
        return 'run';
    }

    public function isAlways() {
        return true;
    }

    public function onExecute($e = null, AbstractEditor $editor = null) {
        static $pty = -1; $pty++;

        FileSystem::open("pty://{$pty}");
    }
}