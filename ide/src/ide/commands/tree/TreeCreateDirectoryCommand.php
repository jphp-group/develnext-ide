<?php
namespace ide\commands\tree;

use ide\editors\AbstractEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\forms\InputMessageBoxForm;
use ide\project\ProjectTree;
use php\gui\UXDialog;
use php\lib\fs;
use php\util\Regex;

class TreeCreateDirectoryCommand extends AbstractMenuCommand
{
    protected $tree;

    public function __construct(ProjectTree $tree)
    {
        $this->tree = $tree;
    }

    public function getIcon()
    {
        return 'icons/folderPlus16.png';
    }

    public function getName()
    {
        return "command.create.dir::Создать папку";
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $file = $this->tree->getSelectedFullPath();

        $dialog = new InputMessageBoxForm(
            'dir.creation::Создание папки',
            'dir.enter.name::Введите название для папки',
            'dir.only.valid.name.hint::* Только валидное имя для папки'
        );

        $dialog->setPattern(new Regex('[^\\?\\<\\>\\*\\:\\|\\"]{1,}', 'i'), 'message.current.name.is.invalid::Данное название некорректное');

        $dialog->showDialog();
        $name = $dialog->getResult();

        if ($name) {
            $dir = $file->isDirectory() ? "$file/$name" : "{$file->getParent()}/$name";

            if (fs::exists($dir)) {
                UXDialog::showAndWait(_('message.file.or.dir.already.exists::Файл или папка с таким названием уже существует.'), 'ERROR');
                $this->onExecute($e, $editor);
                return;
            }

            if (!fs::makeDir($dir)) {
                UXDialog::showAndWait(_('message.cannot.create.dir.with.name::Невозможно создать папку с таким названием.'));
            } else {
                $this->tree->expandSelected();
            }
        }
    }

    public function onBeforeShow($item, AbstractEditor $editor = null)
    {
        parent::onBeforeShow($item, $editor);

        $item->disable = !$this->tree->hasSelectedPath();
    }
}