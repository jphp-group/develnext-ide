<?php


namespace ide\forms;

use ide\Ide;
use php\gui\UXButton;
use php\gui\UXDirectoryChooser;
use php\gui\UXTextField;
use php\lib\fs;

/**
 * Class AndroidSettingsForm
 * @package ide\forms
 *
 * @property UXButton dir_jdk_button
 * @property UXButton dir_sdk_button
 * @property UXButton save_button
 * @property UXTextField dir_jdk
 * @property UXTextField dir_sdk
 */
class AndroidSettingsForm extends AbstractIdeForm {

    /**
     * @throws \Exception
     */
    public function init() {
        parent::init();

        $this->title = _("android.settings.title");

        $this->dir_jdk->text = Ide::get()->getUserConfigValue("android.jdk8.dir");
        $this->dir_sdk->text = Ide::get()->getUserConfigValue("android.sdk.dir");
    }

    /**
     * @param UXTextField $field
     */
    protected function openDirectoryChooser(UXTextField $field) {
        $chooser = new UXDirectoryChooser();

        if ($field->text && fs::isDir($field->text))
            $chooser->initialDirectory = $field->text;

        $dir = $chooser->execute();
        if ($dir != null) $field->text = $dir;
    }

    /**
     * @event dir_jdk_button.click
     */
    public function doJDKButtonClick() {
        $this->openDirectoryChooser($this->dir_jdk);
    }

    /**
     * @event dir_sdk_button.click
     */
    public function doSDKButtonClick() {
        $this->openDirectoryChooser($this->dir_sdk);
    }

    /**
     * @event save_button.click
     * @throws \Exception
     */
    public function doSave() {
        Ide::get()->setUserConfigValue("android.jdk8.dir", $this->dir_jdk->text);
        Ide::get()->setUserConfigValue("android.sdk.dir",  $this->dir_sdk->text);
        Ide::get()->getMainForm()->toast("android.settings.toast", 1000);
        $this->hide();
    }
}