<?php
namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\Ide;
use ide\misc\AbstractCommand;
use ide\settings\ide\IDESettingsGroup;

class SettingsShowCommand extends AbstractCommand
{
    public function getName()
    {
        return 'menu.settings';
    }

    public function getIcon()
    {
        return 'icons/settings16.png';
    }

    public function isAlways()
    {
        return true;
    }

    public function getCategory()
    {
        return 'help';
    }

    /**
     * @param null $e
     * @param AbstractEditor|null $editor
     * @throws \Exception
     */
    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        Ide::get()->getSettings()->open(new IDESettingsGroup());
    }
}