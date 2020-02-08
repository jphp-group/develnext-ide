<?php
namespace ide\project\supports\jppm\settings;

use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class JPPMAppPluginSettingGroup extends AbstractSettingsGroup
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "jppm.run.plugin.settings.name";
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        return [];
    }
}