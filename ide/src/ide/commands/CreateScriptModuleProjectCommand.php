<?php
namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\formats\ScriptModuleFormat;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\lib\Str;

class CreateScriptModuleProjectCommand extends AbstractMenuCommand
{
    public function getName()
    {
        return 'Новый модуль';
    }

    public function getIcon()
    {
        return 'icons/blocks16.png';
    }

    public function getCategory()
    {
        return 'create';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $ide = Ide::get();
        $project = $ide->getOpenedProject();

        if ($project) {

            $name = $ide->getRegisteredFormat(ScriptModuleFormat::class)->showCreateDialog();

            if ($name !== null) {
                $name = str::trim($name);

                if (!FileUtils::validate($name)) {
                    return null;
                }

                $javafx = $project->findSupport('javafx');

                if ($javafx->hasModule($project, $name)) {
                    $dialog = new MessageBoxForm("Модуль '$name' уже существует, хотите его пересоздать?", ['Нет, оставить', 'Да, пересоздать']);
                    if ($dialog->showDialog() && $dialog->getResultIndex() == 0) {
                        return null;
                    }
                }

                $file = $javafx->createModule($project, $name);
                FileSystem::open($file);

                return $name;
            }
        }
    }
}