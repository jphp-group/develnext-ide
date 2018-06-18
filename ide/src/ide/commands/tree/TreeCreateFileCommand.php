<?php
namespace ide\commands\tree;

use ide\editors\AbstractEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\forms\InputMessageBoxForm;
use ide\project\ProjectTree;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\gui\UXDialog;
use php\lib\fs;
use php\util\Regex;

class TreeCreateFileCommand extends AbstractMenuCommand
{
    protected $tree;

    public function __construct(ProjectTree $tree)
    {
        $this->tree = $tree;
    }

    public function getIcon()
    {
        return 'icons/documentPlus16.png';
    }

    public function getName()
    {
        return "entity.file::Файл";
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $file = $this->tree->getSelectedFullPath();

        $dialog = new InputMessageBoxForm(
            'file.creation::Создание файла',
            'file.enter.name.with.ext::Введите название для файла (вместе с расширением):',
            'file.only.valid.name.hint::* Только валидное имя для файла'
        );

        $dialog->setPattern(new Regex('[^\\?\\<\\>\\*\\:\\|\\"]{1,}', 'i'), 'message.current.name.is.invalid::Данное название некорректное');

        $dialog->showDialog();
        $name = $dialog->getResult();

        if ($name) {
            $dir = $file->isDirectory() ? "$file/$name" : "{$file->getParent()}/$name";
            $dir = fs::normalize($dir);

            if (fs::exists($dir)) {
                UXDialog::showAndWait(_('message.file.or.dir.already.exists::Файл или папка с таким названием уже существует.'), 'ERROR');
                $this->onExecute($e, $editor);
                return;
            }

            FileSystem::close($dir, true, false);
            fs::delete($dir);

            uiLater(function () use ($dir) {
                FileUtils::put($dir, '');

                if (!fs::isFile($dir)) {
                    UXDialog::showAndWait(_("message.cannot.create.file.with.name::Невозможно создать файл с таким названием.") . "\n -> {$dir}", 'ERROR');
                } else {
                    $this->tree->expandSelected();
                }
            });
        }
    }

    public function onBeforeShow($item, AbstractEditor $editor = null)
    {
        parent::onBeforeShow($item, $editor);

        $item->disable = !$this->tree->hasSelectedPath();
    }
}