<?php

namespace ide;

use ide\commands\AndroidSettingsCommand;
use ide\project\supports\AndroidProjectSupport;
use ide\project\templates\AndroidProjectTemplate;
use ide\settings\AndroidSettingsGroup;
use ide\tools\AndroidSDKTool;

class AndroidExtension extends AbstractExtension {

    /**
     * @throws IdeException
     */
    public function onRegister() {
        //Ide::get()->registerProjectSupport(AndroidProjectSupport::class);
        //Ide::get()->registerProjectTemplate(new AndroidProjectTemplate());
        //Ide::get()->registerCommand(new AndroidSettingsCommand());
        //Ide::get()->getSettings()->registerSettingGroup(new AndroidSettingsGroup());
        //Ide::get()->getToolManager()->register(new AndroidSDKTool());
    }

    public function onIdeStart() {

    }

    public function onIdeShutdown() {

    }

    public function getName(): string {
        return "plugin.android.name";
    }

    public function getDescription(): string {
        return "plugin.android.description";
    }

    public function getIcon32(): string {
        return "icons/android32.png";
    }
}