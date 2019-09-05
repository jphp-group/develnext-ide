<?php

namespace ide;

use ide\commands\AndroidSettingsCommand;
use ide\project\supports\AndroidProjectSupport;
use ide\project\templates\AndroidProjectTemplate;
use ide\settings\AndroidSettingsGroup;

class AndroidExtension extends AbstractExtension {

    /**
     * @throws IdeException
     */
    public function onRegister() {
        Ide::get()->registerProjectSupport(AndroidProjectSupport::class);
        Ide::get()->registerProjectTemplate(new AndroidProjectTemplate());
        //Ide::get()->registerCommand(new AndroidSettingsCommand());
        Ide::get()->getSettings()->registerSettingGroup(new AndroidSettingsGroup());
    }

    public function onIdeStart() {

    }

    public function onIdeShutdown() {

    }
}