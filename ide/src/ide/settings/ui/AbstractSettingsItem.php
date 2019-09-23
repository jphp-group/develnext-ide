<?php

namespace ide\settings\ui;

use ide\settings\SettingsContext;
use php\gui\UXNode;

abstract class AbstractSettingsItem
{
    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getIcon(): string;

    /**
     * @param SettingsContext $context
     * @return UXNode
     */
    abstract public function makeUi(SettingsContext $context): UXNode;

    /**
     * @param SettingsContext $context
     * @param UXNode $ui
     */
    abstract public function doSave(SettingsContext $context, UXNode $ui);

    /**
     * @param SettingsContext $context
     * @param UXNode $ui
     * @return bool
     */
    abstract public function canSave(SettingsContext $context, UXNode $ui): bool;

    /**
     * @return bool
     */
    public function showBottomButtons(): bool {
        return true;
    }
}