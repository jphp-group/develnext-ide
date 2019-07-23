<?php
namespace ide\commands;

use ide\Ide;
use ide\editors\AbstractEditor;
use ide\misc\AbstractCommand;
use php\framework\Logger;
use php\gui\UXDesktop;
use php\gui\framework\AbstractForm;
use php\gui\framework\FormCollection;
use php\io\IOException;
use php\io\ResourceStream;
use php\lib\str;

class ChangeThemeCommand extends AbstractCommand {
    /**
     * Хз почему, но при использовании Ide::get()->getRegisteredCommand(ChangeThemeCommand::class) 
     * в некоторых случаях возвращается null, поэтому лучше буду обращаться к instance
     * @var SettingsForm
     */
    public static $instance;

    /**
     * Предыдущая используемая тема
     * При добавлении новой темы сначала удаляется старая
     * @var string|null
     */
    protected $prevTheme = null;

    /**
     * Текущая используемая тема
     * @var string|null
     */
    protected $currentTheme = null;

    /**
     * Список доступных тем
     * Темы хранятся по пути $themePath
     * По дефолту берётся первая тема из этого массива
     * @var string[]
     */
    protected $themes = [];

    /**
     * Путь к css файлам с темами
     * Начинается с директории /dn-app-framework/src/
     * @var string
     */
    protected $themePath = '/.theme/ide/';

    public function __construct() {
        parent::__construct();

        $this->registerTheme('light');
        $this->registerTheme('dark');

        FormCollection::onAddEvent([$this, 'applyStylesheet']);
        $this->prevTheme = $this->getCurrentTheme();
        self::$instance = $this;
    }

    /**
     * Получить текущую используемую в среде тему
     * @return string
     */
    public function getCurrentTheme(): string {
        $default = $this->themes[0];
        if(is_null($this->currentTheme)){
            $this->currentTheme = Ide::get()->getUserConfigValue('ide.theme', $default);
        }

        // Проверка, существует ли тема
        return in_array($this->currentTheme, $this->themes) ? $this->currentTheme : ($this->currentTheme = $default);
    }

    /**
     * Установить тему для ide (для применения нужно вызвать метод onExecute)
     * @param string $theme Имя темы (без пути и без расширения)
     */
    public function setCurrentTheme(string $theme){
        // Проверка, существует ли тема
        $theme = (in_array($theme, $this->themes)) ? $theme : $this->themes[0];
        Ide::get()->setUserConfigValue('ide.theme', $theme);
        $this->currentTheme = $theme;

        Logger::info('Set IDE theme: ' . $theme);
    }

    public function unregisterTheme($themeName){
        $themes = array_flip($this->themes);
        unset($themes[$themeName]);
        $this->themes = $themes;
    }

    /**
     * Добавить тему для IDE
     * @param  string $themeName Имя темы, без пути и безс расширения
     * Файл с темой будет искаться по пути $this->themePath / имя-темы .css
     */
    public function registerTheme(string $themeName): bool {
        if(ResourceStream::exists('res:/' . $this->themePath . $themeName . '.css')){
            $this->themes[] = $themeName;
            return true;
        }

        return false;
    }

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
        if($this->prevTheme == $this->currentTheme) return;

        $forms = FormCollection::getForms();
        foreach ($forms as $form) {
            $this->applyStylesheet($form);
        }

        $this->prevTheme = $this->currentTheme;
    }

    public function applyStylesheet(AbstractForm $form){
        if(str::length($this->prevTheme) > 0){
            $prev = $this->themePath . $this->prevTheme . '.css';
            Logger::info('Stylesheet ' . $prev . ' removed from ' . $form->getName());
            $form->removeStylesheet($prev);
        }

        $current = $this->themePath . $this->getCurrentTheme() . '.css';
        Logger::info('Stylesheet ' . $current . ' applied to ' . $form->getName());
        $form->addStylesheet($current);
    }
}