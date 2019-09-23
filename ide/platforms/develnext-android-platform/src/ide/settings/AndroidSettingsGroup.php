<?php

namespace ide\settings;

use ide\settings\items\AndroidSdkManagerItem;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class AndroidSettingsGroup extends AbstractSettingsGroup {

    /**
     * @return string
     */
    public function getName(): string {
        return "project.command.android.settings.name";
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return "";
    }

    /**
     * @return AbstractSettingsItem[]
     */
    public function getItems(): array {
        return [
            new AndroidSdkManagerItem()
        ];
    }
}