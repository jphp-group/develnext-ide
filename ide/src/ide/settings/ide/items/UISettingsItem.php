<?php


namespace ide\settings\ide\items;


use ide\settings\SettingsContext;
use ide\settings\ui\AbstractSettingsItem;
use php\gui\UXNode;
use php\gui\UXTextField;

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
        return new UXTextField();
    }

    /**
     * @param SettingsContext $context
     */
    public function doSave(SettingsContext $context, UXNode $ui) {
        // TODO: Implement doSave() method.
    }

    /**
     * @param SettingsContext $context
     * @return bool
     */
    public function canSave(SettingsContext $context, UXNode $ui): bool {
        return $ui->text == "test";
    }
}