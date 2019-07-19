<?php
namespace ide\commands;

use ide\Ide;
use ide\editors\AbstractEditor;
use ide\misc\AbstractCommand;
use php\gui\UXDesktop;
use php\gui\UXLabel;
use php\gui\UXMenuItem;

class SettingsShowCommand extends AbstractCommand
{
    public function getName()
    {
        return 'menu.settings';
    }

    public function isAlways()
    {
        return true;
    }

    public function makeMenuItem(){
        // Вместо подменю создадим элемент в меню баре
        $menu = Ide::get()->getMainForm()->defineMenuGroup('settings', null);

        // Т.к. UXMenu не имеет события на клик, нужно создать label
        $label = _(new UXLabel('menu.settings', Ide::get()->getImage('icons/settings16.png')));
        $menu->graphic = $label;
        $label->on('click', [$this, 'onExecute']);

        return null;
    }

    public function getCategory()
    {
        return 'settings';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        app()->showFormAndWait('SettingsForm');
    }
}