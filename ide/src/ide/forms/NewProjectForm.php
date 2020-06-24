<?php
namespace ide\forms;

use ide\editors\menu\ContextMenu;
use ide\forms\mixins\DialogFormMixin;
use ide\forms\mixins\SavableFormMixin;
use ide\Ide;
use ide\library\IdeLibraryResource;
use ide\misc\AbstractCommand;
use ide\project\AbstractProjectTemplate;
use ide\systems\ProjectSystem;
use ide\ui\elements\DNButton;
use ide\ui\elements\DNLabel;
use ide\ui\elements\DNListView;
use ide\ui\elements\DNSeparator;
use ide\ui\elements\DNTextField;
use php\gui\event\UXMouseEvent;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXButton;
use php\gui\UXDialog;
use php\gui\UXDirectoryChooser;
use php\gui\UXFileChooser;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\UXListView;
use php\gui\UXSeparator;
use php\gui\UXTextField;
use php\io\File;
use php\lib\fs;
use php\lib\Items;
use php\lib\Str;
use php\util\Regex;

/**
 *
 * @property UXImageView $icon
 * @property UXListView $templateList
 * @property UXTextField $pathField
 * @property UXTextField $nameField
 * @property UXTextField $packageField
 * @property UXButton $createButton
 * @property UXButton $cancelButton
 * @property UXButton $pathButton
 * @property UXLabel $projectNewTitle
 * @property UXLabel $projectNewName
 * @property UXLabel $projectNewPackageName
 * @property UXLabel $projectNewTemplate
 * @property UXLabel $projectNewDir
 * @property UXSeparator $topSeparator
 * @property UXSeparator $bottomSeparator
 *
 * Class NewProjectForm
 * @package ide\forms
 */
class NewProjectForm extends AbstractIdeForm
{
    use DialogFormMixin;
    use SavableFormMixin;

    /** @var AbstractProjectTemplate[] */
    protected $templates;

    /** @var UXFileChooser */
    protected $directoryChooser;

    public function init()
    {
        parent::init();

        $this->directoryChooser = new UXDirectoryChooser();

        $this->icon->image = Ide::get()->getImage('icons/new32.png')->image;
        $this->modality = 'APPLICATION_MODAL';
        $this->title = 'Новый проект';

        $this->pathField->text = $projectDir = Ide::get()->getUserConfigValue('projectDirectory');

        $this->templateList->setCellFactory(function (UXListCell $cell, $template = null) {
            if ($template) {
                $titleName = new DNLabel($template->getName());
                $titleName->font = $titleName->font->withBold();

                $titleDescription = new DNLabel($template->getDescription());

                $title = new UXVBox([$titleName, $titleDescription]);
                $title->spacing = 0;

                $line = new UXHBox([$template instanceof AbstractProjectTemplate ? Ide::get()->getImage($template->getIcon32()) : ico('programEx32'), $title]);
                $line->spacing = 7;
                $line->padding = 5;

                $cell->text = null;
                $cell->graphic = $line;
                $cell->style = '';
            }
        });

        DNListView::applyIDETheme($this->templateList);
        DNTextField::applyIDETheme($this->pathField);
        DNTextField::applyIDETheme($this->packageField);
        DNTextField::applyIDETheme($this->nameField);
        DNButton::applyIDETheme($this->createButton);
        DNButton::applyIDETheme($this->cancelButton);
        DNButton::applyIDETheme($this->pathButton);
        DNLabel::applyIDETheme($this->projectNewTitle);
        DNLabel::applyIDETheme($this->projectNewName);
        DNLabel::applyIDETheme($this->projectNewPackageName);
        DNLabel::applyIDETheme($this->projectNewTemplate);
        DNLabel::applyIDETheme($this->projectNewDir);
        DNSeparator::applyIDETheme($this->topSeparator);
        DNSeparator::applyIDETheme($this->bottomSeparator);
    }

    /**
     * @event show
     */
    public function doShow()
    {
        $templates = Ide::get()->getProjectTemplates();
        $this->templates = Items::toArray($templates);

        $this->templateList->items->clear();

        foreach ($templates as $template) {
            $this->templateList->items->add($template);
        }

        if ($templates) {
            $this->templateList->selectedIndexes = [0];
        }

        $this->nameField->requestFocus();
    }

    /**
     * @event pathButton.action
     */
    public function doChoosePath()
    {
        $path = $this->directoryChooser->execute();

        if ($path !== null) {
            $this->pathField->text = $path;

            Ide::get()->setUserConfigValue('projectDirectory', $path);
        }
    }

    /**
     * @event nameField.keyDown-Enter
     * @event createButton.action
     */
    public function doCreate()
    {
        $template = Items::first($this->templateList->selectedItems);

        if (!$template || !is_object($template)) {
            UXDialog::show(_('project.new.alert.select.template'));
            return;
        }

        $path = File::of($this->pathField->text);

        if (!$path->isDirectory()) {
            if (!$path->mkdirs()) {
                UXDialog::show(_('project.new.error.create.project.directory'), 'ERROR');
                return;
            }
        }

        $name = str::trim($this->nameField->text);

        if (!$name) {
            UXDialog::show(_('project.new.error.name.required'), 'ERROR');
            return;
        }

        if (!fs::valid($name)) {
            UXDialog::show(_('project.new.error.name.invalid') . " \n\n$name", 'ERROR');
            return;
        }

        $package = str::trim($this->packageField->text);

        $regex = new Regex('^[a-z\\_]{2,15}$');

        if (!$regex->test($package)) {
            UXDialog::show(_('project.new.error.package.invalid') . "\n* " . _('project.new.error.package.invalid.description'), 'ERROR');
            return;
        }

        if ($template instanceof IdeLibraryResource) {
            ProjectSystem::import($template->getPath(), "$path/$name", $name);

            $this->hide();
        } else {
            $this->hide();
            $filename = File::of("$path/$name/$name.dnproject");

            /*if (!$filename->createNewFile(true)) {
                UXDialog::show("Невозможно создать файл проекта по выбранному пути\n -> $filename", 'ERROR');
                return;
            }*/

            ProjectSystem::close(false);

            uiLater(function () use ($template, $filename, $package) {
                app()->getMainForm()->showPreloader('Создание проекта ...');
                try {
                    ProjectSystem::create($template, $filename, $package);
                } finally {
                    app()->getMainForm()->hidePreloader();
                }
            });
        }
    }

    /**
     * @event cancelButton.click
     */
    public function doCancel()
    {
        $this->hide();
    }
}
