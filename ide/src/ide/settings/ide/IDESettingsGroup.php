<?php

namespace ide\settings\ide;

use ide\settings\ide\items\PluginsSettingsItem;
use ide\settings\ide\items\UISettingsItem;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class IDESettingsGroup extends AbstractSettingsGroup
{
    /**
     * @return string
     */
    public function getName(): string {
        return "settings.title";
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "settings.ide.description";
    }

    /**
     * @return AbstractSettingsItem[]
     */
    public function getItems(): array
    {
        return [
            new UISettingsItem(),
            //new PluginsSettingsItem()
        ];
    }
}