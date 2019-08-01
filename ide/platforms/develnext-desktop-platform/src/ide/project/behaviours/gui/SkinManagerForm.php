<?php
namespace ide\project\behaviours\gui;

use ide\editors\menu\ContextMenu;
use ide\entity\ProjectSkin;
use ide\forms\AbstractIdeForm;
use ide\forms\MessageBoxForm;
use ide\forms\mixins\DialogFormMixin;
use ide\Ide;
use ide\library\IdeLibrarySkinResource;
use ide\misc\SimpleSingleCommand;
use ide\ui\ListExtendedItem;
use ide\utils\FileUtils;
use php\compress\ZipException;
use php\gui\layout\UXStackPane;
use php\gui\layout\UXVBox;
use php\gui\UXFileChooser;
use php\gui\UXImageView;
use php\gui\UXListCell;
use php\gui\UXListView;
use php\io\File;
use php\io\IOException;
use php\lib\fs;
use php\lib\str;
use timer\AccurateTimer;

/**
 * Class SkinManagerForm
 * @package ide\project\behaviours\gui
 *
 * @property UXListView $list
 * @property UXImageView $icon
 * @property UXVBox $previewContent
 * @property UXStackPane $previewPane
 */
class SkinManagerForm extends AbstractIdeForm
{
    use DialogFormMixin;

    /**
     * Имя файла у скина, который будет установлен по умолчанию, вместо "без скина"
     * Точка в начале имени файла, чтоб он отображался в начале списка
     */
    const DEFAULT_SKIN = ".Modena";

    public function init()
    {
        parent::init();

        $contextMenu = new ContextMenu(null, [
            SimpleSingleCommand::makeWithText('command.choose::Выбрать', 'icons/ok16.png', function () {
                $this->doSelect();
            }),
            '-',
            SimpleSingleCommand::makeWithText('command.export.to.file::Экспортировать в файл', 'icons/save16.png', function () {
                if ($this->list->selectedIndex > 0 && $this->list->selectedItem) {
                    /** @var ProjectSkin $skin */
                    $skin = $this->list->selectedItem->getSkin();

                    $dialog = new UXFileChooser();
                    $dialog->initialFileName = $skin->getUid() . ".zip";
                    $dialog->extensionFilters = [['description' => 'Skin Files (*.zip)', 'extensions' => ['*.zip']]];

                    if ($file = $dialog->showSaveDialog()) {
                        if (fs::ext($file) != 'zip') {
                            $file = "$file.zip";
                        }

                        $this->showPreloader('message.saving::Сохранение ...');

                        FileUtils::copyFileAsync($skin->getFile(), $file, function () {
                            $this->hidePreloader();
                            $this->toast(_('message.skin.successfully.saved.to.file::Скин успешно сохранен в файл.'));
                        });
                    }
                }
            }),
            '-',
            SimpleSingleCommand::makeWithText('command.remove.from.library::Удалить из библиотеки', 'icons/trash16.gif', function () {
                if ($this->list->selectedIndex > 0) {
                    /** @var IdeLibrarySkinResource $skin */
                    $skin = $this->list->selectedItem;

                    if (MessageBoxForm::confirmDelete("скин {$skin->getSkin()->getName()}")) {
                        Ide::get()->getLibrary()->delete($skin);
                        $this->updateList();
                    }
                } else {
                    MessageBoxForm::warning("message.choose.skin.for.removing::Выберите скин для удаления.");
                }
            })
        ]);

        $contextMenu->linkTo($this->list);

        $this->icon->image = ico('brush32')->image;

        $this->list->setCellFactory(function (UXListCell $cell, ?IdeLibrarySkinResource $resource) {
            if ($resource) {
                $cell->text = null;
                if ($skin = $resource->getSkin()) {
                    $desc = $skin->getDescription();

                    if ($skin->getAuthor()) {
                        if ($desc) $desc .= ", ";
                        $desc .= _("skin.entity.author::автор") . " - " . $skin->getAuthor();
                    }

                    if ($skin->getAuthorSite()) {
                        if ($desc) $desc .= " ";
                        $desc .= "({$skin->getAuthorSite()})";
                    }

                    if ($skin->getVersion()) {
                        if ($desc) $desc .= ", ";
                        $desc .= _("skin.entity.version::версия") . " {$skin->getVersion()}";
                    }

                    if (!$desc) {
                        $desc = _("skin.description.empty::Описание отсутствует.");
                    }

                    $cell->graphic = new ListExtendedItem($skin->getName(), str::upperFirst($desc), ico('brush16'));
                }

            } /*else {
                $cell->text = null;
                $cell->graphic = $ui = new ListExtendedItem(_('ui.design.without.skin::(Без скина)'), _('command.remove.skin.from.project::Убрать скин из проекта'), ico('brush16'));
                $ui->setTitleThin(true);
            }*/
        });

        $this->timer = new AccurateTimer(100, function () {
            $this->previewContent->visible = $this->list->selectedIndex > -1;
        });
    }

    /**
     * @event close
     */
    public function doHide()
    {
        $this->timer->stop();
    }

    /**
     * @event cancelButton.action
     */
    public function doCancel(): void
    {
        $this->setResult(null);
        $this->hide();
    }

    /**
     * @event list.click
     */
    public function doListClick()
    {
        $skin = $this->list->selectedItem;

        if ($skin instanceof IdeLibrarySkinResource) {
            $skin = $skin->getSkin();

            $skinDir = Ide::get()->createTempDirectory('skin');
            fs::clean($skinDir);

            $skin->unpack($skinDir);

            $this->previewPane->stylesheets->clear();
            $this->previewPane->stylesheets->add((new File("$skinDir/skin.css"))->toUrl());
        } else {
            $this->previewPane->stylesheets->clear();
        }
    }

    /**
     * @event list.click-2x
     * @event saveButton.action
     */
    public function doSelect()
    {
        if ($this->list->selectedIndex < 0) {
            MessageBoxForm::warning('message.please.choose.skin::Выберите скин ...');
        } else {
            $this->setResult($this->list->selectedItem ? $this->list->selectedItem->getSkin() : ProjectSkin::createEmpty());
            $this->hide();
        }
    }

    /**
     * @event addButton.action
     */
    public function doAdd()
    {
        $dlg = new UXFileChooser();
        $dlg->extensionFilters = [['description' => 'Skin Files (*.zip)', 'extensions' => ['*.zip']]];

        retry:
        if ($file = $dlg->execute()) {
            try {
                if ($skin = ProjectSkin::createFromZip($file)) {
                    $ideLibrary = Ide::get()->getLibrary();

                    $skinDir = $ideLibrary->getResourceDirectory('skins');

                    $destFile = "$skinDir/" . $skin->getUid() . ".zip";

                    if (fs::isFile($destFile)) {
                        if (!MessageBoxForm::confirm(_("message.confirm.skin.already.exists.replace.it::Скин с ID ({0}) уже существует в библиотеке, хотите заменить его новым?", $skin->getUid()))) {
                            goto retry;
                        }
                    }

                    FileUtils::copyFile($file, $destFile);
                    $ideLibrary->updateCategory('skins');

                    $this->updateList();
                    $this->toast(_('message.skin.successfully.added::Скин был успешно добавлен'));
                }
            } catch (ZipException $e) {
                MessageBoxForm::warning("message.cannot.read.zip.file.skin.not.added::Ошибка чтения zip файла, скин не был добавлен.", $this);
            } catch (IOException $e) {
                MessageBoxForm::warning("message.connot.read.file.skin.not.added::Ошибка чтения файла, скин не был добавлен.", $this);
            }
        }
    }

    public function updateList()
    {
        $this->list->items->clear(); // В списке не будет пункта "без скина"
        $resources = Ide::get()->getLibrary()->getResources('skins');

        foreach ($resources as $resource) {
            if ($resource->isValid()) {
                $this->list->items->add($resource);
            }
        }
    }

    /**
     * @event showing
     */
    public function doShowing(): void
    {
        $this->timer->start();
        $this->updateList();
    }
}