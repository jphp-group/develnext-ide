<?php
namespace ide\commands;

use ide\commands\theme\DarkTheme;
use ide\commands\theme\IDETheme;
use ide\commands\theme\LightTheme;
use ide\Ide;
use ide\editors\AbstractEditor;
use ide\misc\AbstractCommand;
use php\framework\Logger;
use php\gui\framework\AbstractForm;
use php\gui\framework\FormCollection;
use php\io\ResourceStream;
use php\lib\str;

class ChangeThemeCommand extends AbstractCommand {

    public static $instance;

    /**
     * Предыдущая используемая тема
     * При добавлении новой темы сначала удаляется старая
     * @var IDETheme|null
     */
    protected $prevTheme = null;

    /**
     * Текущая используемая тема
     * @var IDETheme|null
     */
    protected $currentTheme = null;

    /**
     * Список доступных тем
     * Темы хранятся по пути $themePath
     * По дефолту берётся первая тема из этого массива
     * @var IDETheme[]
     */
    protected $themes = [];

    /**
     * ChangeThemeCommand constructor.
     * @throws \Exception
     */
    public function __construct() {
        parent::__construct();

        $this->registerTheme(new LightTheme());
        $this->registerTheme(new DarkTheme());

        FormCollection::onAddEvent([$this, 'applyStylesheet']);
        $this->prevTheme = $this->getCurrentTheme();
        self::$instance = $this;

        $this->getCurrentTheme()->onApply();
    }

    /**
     * @return IDETheme
     * @throws \Exception
     */
    public function getCurrentTheme(): IDETheme {
        $default = $this->themes[0]->getName();
        if(is_null($this->currentTheme)) {
            foreach ($this->themes as $theme) {
                if ($theme->getName() == Ide::get()->getUserConfigValue('ide.theme', $default))
                    $this->currentTheme = $theme;
            }

            if (is_null($this->currentTheme))
                $this->currentTheme = $this->themes[0];
        }

        return $this->currentTheme;
    }

    /**
     * @param string $theme
     * @throws \Exception
     */
    public function setCurrentTheme(string $theme){
        foreach ($this->themes as $t) {
            if ($t->getName() == $theme) {
                Ide::get()->setUserConfigValue('ide.theme', $theme);
                $this->currentTheme = $t;
            }
        }

        if ($this->currentTheme->getName() != $theme)
            $this->currentTheme = $this->themes[0];

        $this->currentTheme->onApply();

        Logger::info('Set IDE theme: ' . $theme);
    }

    public function unregisterTheme($themeName){
        $themes = array_flip($this->themes);
        unset($themes[$themeName]);
        $this->themes = $themes;
    }

    /**
     * @param IDETheme $theme
     * @throws \Exception
     */
    public function registerTheme(IDETheme $theme) {
        $this->themes[] = $theme;
    }

    /**
     * @return IDETheme[]
     */
    public function getThemes(): array {
        return $this->themes;
    }

    public function getCategory(){
        return 'theme';
    }

    public function getName(){
        return 'theme.changer';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        if($this->prevTheme === $this->currentTheme) return;

        $forms = FormCollection::getForms();
        foreach ($forms as $form) {
            $this->applyStylesheet($form);
        }

        $this->prevTheme = $this->currentTheme;
    }

    public function applyStylesheet(AbstractForm $form){
        if(str::length($this->prevTheme) > 0){
            $prev = $this->prevTheme->getCSSFile();
            Logger::info('Stylesheet ' . $prev . ' removed from ' . $form->getName());
            $form->removeStylesheet($prev);
        }

        $current = $this->getCurrentTheme()->getCSSFile();
        Logger::info('Stylesheet ' . $current . ' applied to ' . $form->getName());
        $form->addStylesheet($current);
    }
}