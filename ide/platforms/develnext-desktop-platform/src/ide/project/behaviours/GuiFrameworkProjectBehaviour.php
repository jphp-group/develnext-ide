<?php

namespace ide\project\behaviours;

use ide\editors\AbstractEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\Ide;
use ide\IdeException;
use ide\project\AbstractProjectBehaviour;
use ide\project\supports\JavaFXProjectSupport;
use ide\systems\FileSystem;
use php\gui\UXMenu;
use php\gui\UXMenuItem;
use php\io\File;
use php\util\Configuration;
use timer\AccurateTimer;

class GuiFrameworkProjectBehaviour_ProjectTreeMenuCommand extends AbstractMenuCommand
{
    protected UXMenu $menu;
    private JavaFXProjectSupport $gui;

    /**
     * GuiFrameworkProjectBehaviour_ProjectTreeMenuCommand constructor.
     */
    public function __construct(JavaFXProjectSupport $gui)
    {
        $this->menu = new UXMenu();
        $this->gui = $gui;
    }

    public function withBeforeSeparator()
    {
        return true;
    }

    public function makeMenuItem()
    {
        $menu = $this->menu;
        $menu->text = $this->getName();
        $menu->graphic = Ide::get()->getImage($this->getIcon());

        return $menu;
    }

    public function getIcon()
    {
        return 'icons/dirs16.png';
    }


    public function getName()
    {
        return "command.all.project::Весь проект";
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
    }

    /**
     * @param UXMenu|UXMenuItem $item
     * @param AbstractEditor|null $editor
     * @throws \php\lang\IllegalArgumentException
     * @throws IdeException
     */
    public function onBeforeShow($item, AbstractEditor $editor = null)
    {
        $menu = $this->menu;
        $menu->items->clear();

        $project = Ide::project();

        foreach ([$this->gui->getFormEditors($project), $this->gui->getModuleEditors($project), $this->gui->getSpriteEditors()] as $i => $editors) {
            if ($i > 0 && $editors) {
                $menu->items->add(UXMenuItem::createSeparator());
            }

            /** @var AbstractEditor[] $editors */
            foreach ($editors as $editor) {
                $menuItem = new UXMenuItem($editor->getTitle(), Ide::get()->getImage($editor->getIcon()));
                $menu->items->add($menuItem);

                if (FileSystem::isOpened($editor->getFile())) {
                    $menuItem->style = '-fx-text-fill: blue;';
                }

                $menuItem->on('action', function () use ($editor) {
                    FileSystem::open($editor->getFile());
                });
            }
        }
    }
}

/**
 * Class GuiFrameworkProjectBehaviour
 * @package ide\project\behaviours
 */
class GuiFrameworkProjectBehaviour extends AbstractProjectBehaviour
{
    /** @var string */
    protected $mainForm = '';

    /**
     * @var Configuration
     */
    protected $applicationConfig;

    /**
     * @var string app.uuid from application.conf
     */
    protected $appUuid;

    /**
     * @var File
     */
    protected $ideStylesheetFile;

    /**
     * @var int
     */
    protected $ideStylesheetFileTime;

    /**
     * @var AccurateTimer
     */
    protected $ideStylesheetTimer;

    /**
     * @return int
     */
    public function getPriority()
    {
        return self::PRIORITY_LIBRARY;
    }

    /**
     * ...
     */
    public function inject()
    {
        //$this->project->on('makeSettings', [$this, 'doMakeSettings']);
        //$this->project->on('updateSettings', [$this, 'doUpdateSettings']);
    }

    public function doClose()
    {
        /*if ($this->ideStylesheetTimer) {
            $this->ideStylesheetTimer->stop();
        }*/

        // Clear all styles for MainForm.
        /*if ($form = Ide::get()->getMainForm()) {
            $path = "file:///" . str::replace($this->ideStylesheetFile, "\\", "/");
            $form->removeStylesheet($path);
        }*/
    }

    /*public function doUpdateSettings(CommonProjectControlPane $editor = null)
    {
        if ($this->uiSplashLabel) {
            $this->uiSplashLabel->text = $this->splashData['src'] ?: 'entity.image.empty::(Нет изображения)';
            $this->uiSplashLabel = _($this->uiSplashLabel);
        }

        if ($this->uiSplashOnTop) {
            $this->uiSplashOnTop->selected = (bool)$this->splashData['alwaysOnTop'];
        }

        if ($this->uiSplashAutoHide) {
            $this->uiSplashAutoHide->selected = (bool)$this->splashData['autoHide'];
        }
    }

    public function doMakeSettings(CommonProjectControlPane $editor)
    {
        $title = _(new UXLabel('gui.option.splash::Заставка (Splash):'));
        $title->font = $title->font->withBold();

        $label = _(new UXLabel('entity.image.empty::(Нет изображения)'));
        $label->textColor = 'gray';
        $button = _(new UXButton('command.choose::Выбрать'));
        $button->classes->add('icon-open');

        $this->uiSplashLabel = $label;

        $button->on('action', function () use ($label) {
            $dialog = new ImagePropertyEditorForm();

            if ($dialog->showDialog()) {
                $this->splashData['src'] = $dialog->getResult() ? "/{$dialog->getResult()}" : null;
                $label->text = $this->splashData['src'] ?: 'entity.image.empty::(Нет изображения)';
                $label = _($label);

                $this->saveLauncherConfig();
            }
        });

        $UXHBox = new UXHBox([$button, $label], 10);
        $UXHBox->alignment = 'CENTER_LEFT';

        $this->uiSplashOnTop = $fxSplashOnTop = _(new UXCheckbox('gui.option.splash.always.on.top::Заставка всегда поверх окон'));
        $fxSplashAutoHide = new UXCheckbox('gui.option.splash.hide.after.start::Автоматически скрывать заставку после старта');
        $fxSplashAutoHide->tooltipText = 'gui.option.splash.hide.after.start.help::Чтобы скрыть заставку через код используйте app()->hideSplash()';
        $this->uiSplashAutoHide = _($fxSplashAutoHide);

        $fxSplashOnTop->on('mouseUp', function () {
            $this->splashData['alwaysOnTop'] = $this->uiSplashOnTop->selected;
            $this->saveLauncherConfig();
        });

        $fxSplashAutoHide->on('mouseUp', function () {
            $this->splashData['autoHide'] = $this->uiSplashAutoHide->selected;
            $this->saveLauncherConfig();
        });

        $wrap = new UXVBox([$title, $UXHBox, $fxSplashOnTop, $fxSplashAutoHide], 5);

        $editor->addSettingsPane($wrap);
    }*/

    public function doOpen()
    {
    }

    public function doRecover()
    {
        /*$this->_recoverDirectories();

        $this->project->defineFile('src/.system/application.conf', new GuiApplicationConfFileTemplate($this->project));*/
    }


    protected function _recoverDirectories()
    {
        /*$this->project->makeDirectory('src/');
        $this->project->makeDirectory('src/.data');
        $this->project->makeDirectory('src/.data/img');
        $this->project->makeDirectory('src/.system');
        $this->project->makeDirectory('src/JPHP-INF');

        $this->project->makeDirectory("src/{$this->project->getPackageName()}");
        $this->project->makeDirectory("src/{$this->project->getPackageName()}/forms");
        $this->project->makeDirectory("src/{$this->project->getPackageName()}/modules");*/
    }
}