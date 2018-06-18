<?php
namespace ide\commands\tree;

use ide\editors\AbstractEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\project\ProjectTree;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\gui\UXDesktop;
use php\gui\UXDialog;
use php\lang\Process;
use php\lib\fs;

class TreeDeleteFileCommand extends AbstractMenuCommand
{
    protected $tree;

    public function __construct(ProjectTree $tree)
    {
        $this->tree = $tree;
    }

    public function getIcon()
    {
        return 'icons/delete16.png';
    }

    public function getName()
    {
        return "command.delete::Удалить";
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $file = $this->tree->getSelectedFullPath();

        if ($file) {
            $name = fs::name($file);

            $msg = new MessageBoxForm(
                _("message.confirm.delete::Вы уверены, что хотите удалить [{0}]?", $name),
                ['btn.yes.delete::Да, удалить', 'btn.no.cancel']
            );
            $msg->makeWarning();

            if ($msg->showDialog()) {
                if ($msg->getResultIndex() == 0) {
                    if (fs::isDir($file)) {
                        $success = FileUtils::deleteDirectory($file);
                    } else {
                        $editor = FileSystem::fetchEditor($file);

                        if ($editor) {
                            $editor->delete();
                        }

                        fs::delete($file);
                        $success = !fs::isFile($file);
                    }

                    if (!$success) {
                        UXDialog::showAndWait(_("error.delete::Ошибка удаления {0}", $file), 'ERROR');
                    }
                }
            }
        }
    }

    public function onBeforeShow($item, AbstractEditor $editor = null)
    {
        parent::onBeforeShow($item, $editor);

        $file = $this->tree->getSelectedFullPath();
        $item->disable = !$file;

        if ($file) {
            $item->text = _($this->getName()) . ' [' . $file->getName() . ']';
        }
    }
}