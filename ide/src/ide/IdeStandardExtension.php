<?php
namespace ide;

use ide\settings\ide\IDESettingsGroup;
use ide\systems\DialogSystem;

class IdeStandardExtension extends AbstractExtension
{
    /**
     * @throws \Exception
     */
    public function onRegister()
    {
        DialogSystem::registerDefaults();

        Ide::get()->getSettings()->registerSettingGroup(new IDESettingsGroup());
    }

    public function onIdeStart() {

    }

    public function onIdeShutdown() {

    }

    public function getName(): string {
        return "plugin.develnext.name";
    }

    public function getDescription(): string {
        return "plugin.develnext.description";
    }

    public function isSystem(): bool {
        return true;
    }
}