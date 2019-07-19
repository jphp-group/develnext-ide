<?php
namespace ide\commands;

use ide\Ide;
use ide\editors\AbstractEditor;
use ide\misc\AbstractCommand;
use php\framework\Logger;
use php\gui\UXDesktop;
use php\gui\framework\AbstractForm;
use php\gui\framework\FormCollection;
use php\lib\str;

class ChangeThemeCommand extends AbstractCommand {
    public static $instance;

    protected $prevTheme = null;
    protected $currentTheme = null;

    public function __construct() {
        parent::__construct();
        self::$instance = $this;
        FormCollection::onAddEvent([$this, 'applyStylesheet']);
    }

    public function getCurrentTheme(){
        if(is_null($this->currentTheme)){
            $this->currentTheme = Ide::get()->getUserConfigValue('ide.theme', 'light');
        }

        return $this->currentTheme;
    }

    public function setCurrentTheme(string $theme){
        Ide::get()->setUserConfigValue('ide.theme', $theme);
        $this->currentTheme = $theme;
    }

    public function getName()
    {
        return 'theme.changer';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        if($this->prevTheme == $this->currentTheme) return;

        $forms = FormCollection::getForms();
        foreach ($forms as $form) {
            $this->applyStylesheet($form);
        }

        $this->prevTheme = $this->currentTheme;
    }

    public function applyStylesheet(AbstractForm $form){
        if(str::length($this->prevTheme) > 0){
            $prev = '/php/gui/framework/styles/' . $this->prevTheme . '.css';
            Logger::debug('Stylesheet ' . $prev . ' removed from ' . $form->getName());
            $form->removeStylesheet($prev);
        }

        $current = '/php/gui/framework/styles/' . $this->getCurrentTheme() . '.css';
        Logger::debug('Stylesheet ' . $current . ' applied to ' . $form->getName());
        $form->addStylesheet($current);
    }
}