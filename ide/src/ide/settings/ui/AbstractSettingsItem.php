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
     */
    abstract public function doSave(SettingsContext $context);

    /**
     * @param SettingsContext $context
     */
    abstract public function doRestore(SettingsContext $context);
}