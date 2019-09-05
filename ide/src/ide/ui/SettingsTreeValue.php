<?php

namespace ide\ui;

use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class SettingsTreeValue {

    /**
     * @var AbstractSettingsItem|AbstractSettingsGroup
     */
    private $item;

    /**
     * SettingsTreeValue constructor.
     * @param AbstractSettingsItem|AbstractSettingsGroup $item
     */
    public function __construct($item) {
        $this->item = $item;
    }

    public function __toString() {
        return (string) _($this->item->getName());
    }

    /**
     * @return AbstractSettingsGroup|AbstractSettingsItem
     */
    public function getItem() {
        return $this->item;
    }
}