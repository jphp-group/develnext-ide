<?php

namespace ide\settings\items;

use ide\Ide;
use ide\settings\SettingsContext;
use ide\settings\ui\AbstractSettingsItem;
use ide\tools\AndroidSDKTool;
use php\gui\UXButton;
use php\gui\UXLabel;
use php\gui\UXNode;

class AndroidSdkManagerItem extends AbstractSettingsItem {

    /**
     * @return string
     */
    public function getName(): string {
        return "settings.android.item";
    }

    /**
     * @return string
     */
    public function getIcon(): string {
        return "icons/android16.png";
    }

    /**
     * @param SettingsContext $context
     * @return UXNode
     */
    public function makeUi(SettingsContext $context): UXNode {
        $button = new UXButton("Установить SDK");
        $button->on("action", function () {
            Ide::get()->getSettings()->hide();
            Ide::get()->getToolManager()->install(["Android SDK"], function () {
                Ide::get()->getSettings()->open($this);
            });
        });

        return $button;
    }

    /**
     * @param SettingsContext $context
     * @param UXNode $ui
     */
    public function doSave(SettingsContext $context, UXNode $ui) {
        // just ignore ...
    }

    /**
     * @param SettingsContext $context
     * @param UXNode $ui
     * @return bool
     */
    public function canSave(SettingsContext $context, UXNode $ui): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function showBottomButtons(): bool {
        return false;
    }
}