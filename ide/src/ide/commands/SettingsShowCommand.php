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
        return 'fa:gears,16px,';
    }

    public function isAlways()
    {
        return true;
    }

    public function getCategory()
    {
        return 'help';
    }

    public function makeUiForHead()
    {
        return $this->makeGlyphButton();
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