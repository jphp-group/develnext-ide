<?php


namespace ide\settings\ide\items;


use ide\settings\SettingsContext;
use ide\settings\ui\AbstractSettingsItem;
use php\gui\UXButton;
use php\gui\UXNode;

class UISettingsItem extends AbstractSettingsItem {

    /**
     * @return string
     */
    public function getName(): string {
        return "settings.ide.ui.item";
    }

    /**
     * @return string
     */
    public function getIcon(): string {
        return "icons/label16.png";
    }

    /**
     * @param SettingsContext $context
     * @return UXNode
     */
    public function makeUi(SettingsContext $context): UXNode {
        return new UXButton("Hello, new Settings API!");
    }

    /**
     * @param SettingsContext $context
     */
    public function doSave(SettingsContext $context) {
        // TODO: Implement doSave() method.
    }

    /**
     * @param SettingsContext $context
     */
    public function doRestore(SettingsContext $context) {
        // TODO: Implement doRestore() method.
    }
}