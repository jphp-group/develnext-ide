<?php


namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\Ide;
use ide\project\templates\AndroidProjectTemplate;
use ide\settings\items\AndroidSdkManagerItem;
use php\gui\UXButton;

class AndroidSettingsCommand extends AbstractProjectCommand {

    /**
     * @return UXButton
     * @throws \Exception
     */
    public function makeUiForRightHead() {
        $button = new UXButton();
        $button->graphic = Ide::getImage("icons/android16.png", [16, 16]);
        $button->visible = false;

        $button->on("action", function () {
            Ide::get()->getSettings()->open(new AndroidSdkManagerItem());
        });

        Ide::get()->on("openProject", function () use ($button) {
            var_dump(Ide::project()->getTemplate());
            if (Ide::project()->getTemplate() instanceof AndroidProjectTemplate)
                $button->visible = true;
        });

        Ide::get()->on("closeProject", function () use ($button) {
            $button->visible = false;
        });

        return $button;
    }

    public function isAlways() {
        return true;
    }

    public function makeMenuItem() {
        return null;
    }

    public function getName() {
        return "project.command.android.settings.name";
    }

    public function getIcon() {
        return "icons/settings16.png";
    }

    /**
     * @param null $e
     * @param AbstractEditor|null $editor
     * @throws \Exception
     */
    public function onExecute($e = null, AbstractEditor $editor = null) {

    }

    /**
     * @deprecated
     * @return string
     */
    public static function getJDKDir() {
        return "";
    }

    /**
     * @deprecated
     * @return string
     */
    public static function getSDKDir() {
        return "";
    }
}