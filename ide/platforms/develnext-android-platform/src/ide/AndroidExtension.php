<?php

namespace ide;

use ide\commands\AndroidSettingsCommand;
use ide\project\supports\AndroidProjectSupport;
use ide\project\templates\AndroidProjectTemplate;

class AndroidExtension extends AbstractExtension {

    /**
     * @throws IdeException
     */
    public function onRegister() {
        Ide::get()->registerProjectSupport(AndroidProjectSupport::class);
        Ide::get()->registerProjectTemplate(new AndroidProjectTemplate());
        Ide::get()->registerCommand(new AndroidSettingsCommand());
    }

    public function onIdeStart() {

    }

    public function onIdeShutdown() {

    }
}