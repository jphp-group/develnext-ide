<?php


namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\forms\AndroidSettingsForm;
use ide\Ide;
use ide\project\Project;
use ide\project\templates\AndroidProjectTemplate;
use php\gui\UXButton;
use php\gui\UXForm;

class AndroidSettingsCommand extends AbstractProjectCommand {

    /**
     * @var UXButton
     */
    private $button;

    /**
     * @var UXForm
     */
    private $settingForm;

    public function __construct() {
        parent::__construct();

        $this->button = new UXButton($this->getName());
        $this->button->graphic = Ide::getImage($this->getIcon());
        $this->button->on("click", [$this, "onExecute"]);

        Ide::get()->bind('openProject', function (Project $project) {
            if ($project->getTemplate() instanceof AndroidProjectTemplate)
                $this->button->show();
        });

        Ide::get()->bind('closeProject', function (Project $project) {
            $this->button->hide();
        });

        $this->settingForm = new AndroidSettingsForm();
    }

    public function makeUiForRightHead() {
        return _($this->button);
    }

    public function isAlways() {
        return true;
    }

    public function getName() {
        return "project.command.android.settings.name";
    }

    public function getIcon() {
        return "icons/settings16.png";
    }

    public function onExecute($e = null, AbstractEditor $editor = null) {
        if ($this->settingForm->visible)
            $this->settingForm->requestFocus();
        else $this->settingForm->showAndWait();
    }
}