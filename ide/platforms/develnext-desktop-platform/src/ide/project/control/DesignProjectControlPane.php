<?php

namespace ide\project\control;

use ide\editors\AbstractEditor;
use ide\editors\CodeEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\editors\menu\ContextMenu;
use ide\entity\ProjectSkin;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\Logger;
use ide\misc\SimpleSingleCommand;
use ide\project\behaviours\gui\SkinManagerForm;
use ide\project\behaviours\gui\SkinSaveDialogForm;
use ide\project\Project;
use ide\project\supports\JavaFXProjectSupport;
use ide\ui\elements\DNAnchorPane;
use ide\ui\elements\DNLabel;
use ide\ui\elements\DNSeparator;
use ide\utils\FileUtils;
use ide\utils\UiUtils;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\text\UXFont;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\lib\fs;

/**
 * @package ide\project\control
 */
class DesignProjectControlPane extends AbstractProjectControlPane
{
    /**
     * @var CodeEditor
     */
    protected $editor;

    /**
     * @var UXLabel
     */
    protected $uiSkinName;

    /**
     * @var bool
     */
    protected $loaded = false;


    public function getName()
    {
        return "ui.design::Внешний вид";
    }

    public function getDescription()
    {
        return "ui.css.style.and.design::CSS стиль и дизайн";
    }

    public function getIcon()
    {
        return 'icons/design16.png';
    }

    public function save()
    {
        if ($this->editor) {
            $this->editor->save();
        }
    }

    public function open()
    {
        if ($this->editor) {
            $this->editor->open();
            if (method_exists($this->editor, 'refreshUi')) {
                $this->editor->refreshUi();
            }
        }

        /** @var JavaFXProjectSupport $gui */
        if ($gui = Project::findSupportOfCurrent('javafx')) {
            $project = Ide::project();

            $skin = $gui->getCurrentSkin($project);

            if ($project->_skinChecked) return;

            $project->_skinChecked = true;

            if ($skin && $skin->getUid() && !Ide::get()->getLibrary()->getResource('skins', $skin->getUid())) {
                if (MessageBoxForm::confirm(
                    _('ui.design.message.project.has.unknown.skin::Проект содержит скин ({0}), которого нет в вашей библиотеке, хотите сохранить его в библиотеку?', $skin->getName())
                )) {

                    $ideLibrary = Ide::get()->getLibrary();
                    $skinFile = $ideLibrary->getResourceDirectory('skins') . "/{$skin->getUid()}.zip";

                    $cssFile = $project->getSrcFile('.theme/skin/skin.css');

                    $dir = fs::parent($cssFile);
                    $additionalFiles = [];

                    fs::scan($dir, function ($filename) use ($dir, &$additionalFiles, $cssFile) {
                        if (fs::isFile($filename)) {
                            $name = FileUtils::relativePath($dir, $filename);

                            if ($name !== 'skin.json' && $name !== fs::name($cssFile)) {
                                $additionalFiles[$name] = $filename;
                            }
                        }
                    });

                    $skin->saveToZip($cssFile, $skinFile, $additionalFiles);
                    $ideLibrary->updateCategory('skins');

                    if (fs::isFile($skinFile)) {
                        Ide::toast(_('ui.design.message.skin.successfully.saved::Скин успешно сохранен в библиотеке скинов'));
                    } else {
                        MessageBoxForm::warning(_('ui.design.message.skin.failed.to.save::Ошибка сохранения скина'));
                    }
                }
            }
        }
    }

    public function load()
    {
        if ($this->editor) {
            $this->editor->load();

            $this->loaded = true;
        }
    }


    protected function makeActionsUi()
    {
        $this->uiSkinName = new DNLabel();
        $icon = ico('brush32');
        UXHBox::setMargin($icon, [0, 5, 0, 0]);


        $menu = new ContextMenu(null, [
            new DesignProjectControlPane_SkinClearCommand($this),
            '-',
            new DesignProjectControlPane_SkinConvertToTheme($this),
        ]);

        $pane = UiUtils::makeCommandPane([
            $icon,
            $this->uiSkinName,
            '-',
            $menu->makeButton('command.select.skin::Выбрать скин', ico('brush16'), function () {
                /** @var JavaFXProjectSupport $gui */
                if ($gui = Project::findSupportOfCurrent('javafx')) {
                    try {
                        $manager = new SkinManagerForm();
                        if ($manager->showDialog() && $manager->getResult()) {
                            /** @var ProjectSkin $skin */
                            $skin = $manager->getResult();

                            if ($skin->isEmpty()) {
                                $gui->clearSkin(Ide::project());
                            } else {
                                $gui->applySkin(Ide::project(), $manager->getResult());
                            }

                            $this->refresh();
                        }
                    } catch (\Exception $e) {
                        Logger::exception($e->getMessage(), $e);
                        MessageBoxForm::warning($e->getMessage());
                    }
                }
            }),
            '-',
            SimpleSingleCommand::makeWithText('command.save.css.as.skin::Сохранить CSS как скин', 'icons/save16.png', function () {
                $dialog = new SkinSaveDialogForm($this->editor->getFile());
                $dialog->showAndWait();
            })
        ]);

        $pane->spacing = 5;

        return $pane;
    }

    /**
     * @return UXNode
     */
    protected function makeUi()
    {
        $path = Ide::project()->getSrcFile('.theme/style.fx.css');
        $this->editor = Ide::get()->getFormat($path)->createEditor($path);
        $this->editor->setTabbed(false);
        //$this->editor->loadContentToArea();

        $cssEditor = $this->editor->makeUi();

        $ui = new UXVBox([$this->makeActionsUi(), new DNSeparator(), $cssEditor], 5);
        UXVBox::setVgrow($cssEditor, 'ALWAYS');
        DNAnchorPane::applyIDETheme($ui);

        return $ui;
    }

    /**
     * Refresh ui and pane.
     */
    public function refresh()
    {
        if ($this->editor) {
            //$this->editor->loadContentToAreaIfModified();
        }

        if ($this->ui) {
            $this->ui->requestFocus();

            uiLater(function () {
                $this->editor->requestFocus();
            });

            $this->uiSkinName->text = 'ui.design.no.skin.selected::(Скин не выбран)';
            $this->uiSkinName->textColor = 'gray';
            $this->uiSkinName->font = UXFont::of('System', UiUtils::fontSize());

            _($this->uiSkinName);

            /** @var JavaFXProjectSupport $gui */
            if ($gui = Project::findSupportOfCurrent('javafx')) {
                $skin = $gui->getCurrentSkin(Ide::project(), $gui);

                if ($skin) {
                    $this->uiSkinName->text = $skin->getName();
                    $this->uiSkinName->textColor = 'black';
                    $this->uiSkinName->font = UXFont::of('System', UiUtils::fontSize(), 'BOLD');
                }
            }
        }
        // nop.
    }
}

class DesignProjectControlPane_SkinConvertToTheme extends AbstractMenuCommand
{
    /**
     * @var DesignProjectControlPane
     */
    private $pane;

    /**
     * DesignProjectControlPane_SkinClearCommand constructor.
     * @param DesignProjectControlPane $pane
     */
    public function __construct(DesignProjectControlPane $pane)
    {
        $this->pane = $pane;
    }

    public function getName()
    {
        return "command.convert.skin.to.project.styles::Конвертировать скин в стили проекта";
    }

    public function getIcon()
    {
        return 'icons/convert16.png';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        if (MessageBoxForm::confirm('ui.design.confirm.message.all.styles.replaced.with.skin::Все стили проекта будут заменены стилями скина, Вы уверены?')) {
            $gui = Project::findSupportOfCurrent('javafx');
            if ($gui) {
                $gui->convertSkinToTheme(Ide::project());
                $this->pane->refresh();
            }
        }
    }

    public function onBeforeShow($item, AbstractEditor $editor = null)
    {
        parent::onBeforeShow($item, $editor);

        /** @var JavaFXProjectSupport $gui */
        $gui = Project::findSupportOfCurrent('javafx');
        $item->enabled = $gui && $gui->getCurrentSkin(Ide::project()) != null;
    }
}

class DesignProjectControlPane_SkinClearCommand extends AbstractMenuCommand
{
    /**
     * @var DesignProjectControlPane
     */
    private $pane;

    /**
     * DesignProjectControlPane_SkinClearCommand constructor.
     * @param DesignProjectControlPane $pane
     */
    public function __construct(DesignProjectControlPane $pane)
    {
        $this->pane = $pane;
    }

    public function getName()
    {
        return "ui.design.without.skin::(Без скина)";
    }

    public function getIcon()
    {
        return 'icons/clear16.png';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        if ($gui = Project::findSupportOfCurrent('javafx')) {
            $gui->clearSkin(Ide::project());
            $this->pane->refresh();
        }
    }

    public function onBeforeShow($item, AbstractEditor $editor = null)
    {
        parent::onBeforeShow($item, $editor);

        /** @var JavaFXProjectSupport $gui */
        $gui = Project::findSupportOfCurrent('javafx');
        $item->enabled = $gui && $gui->getCurrentSkin(Ide::project()) != null;
    }
}
