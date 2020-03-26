<?php
namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\editors\FormEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\formats\GuiFormFormat;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\lib\Str;

class CreateFormProjectCommand extends AbstractMenuCommand
{
    public function getName()
    {
        return 'Новая форма';
    }

    public function getIcon()
    {
        return 'icons/window16.png';
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
            $javafx = $project->findSupport('javafx');

            $format = $ide->getRegisteredFormat(GuiFormFormat::class);
            $name = $format->showCreateDialog();

            if ($name !== null) {
                $name = str::trim($name);

                if (!FileUtils::validate($name)) {
                    return;
                }

                if ($javafx->hasForm($project, $name)) {
                    $dialog = new MessageBoxForm("Форма '$name' уже существует, хотите её пересоздать?", ['Нет, оставить', 'Да, пересоздать']);
                    if ($dialog->showDialog() && $dialog->getResultIndex() == 0) {
                        return;
                    }
                }

                $file = $javafx->createForm($project, $name);

                /** @var FormEditor $editor */
                $editor = FileSystem::fetchEditor($file);
                $editor->getConfig()->set("form.title", $name);
                $editor->saveConfig();

                FileSystem::open($file);

                if (!$javafx->getMainForm($project) && sizeof($javafx->getFormEditors($project)) < 2) {
                    $dlg = new MessageBoxForm(
                        "У вашего проекта нет главной формы, хотите сделать форму '$name' главной?", ['Да, сделать главной', 'Нет']
                    );

                    if ($dlg->showDialog() && $dlg->getResultIndex() == 0) {
                        $javafx->setMainForm($project, $name);
                        Ide::toast("Форма '$name' теперь главная в вашем проекте");
                    }
                }
            }
        }
    }
}