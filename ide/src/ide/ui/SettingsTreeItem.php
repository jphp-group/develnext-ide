<?php

namespace ide\ui;

use ide\Ide;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;
use php\gui\UXTreeItem;

class SettingsTreeItem extends UXTreeItem {

    /**
     * @var AbstractSettingsItem|AbstractSettingsGroup
     */
    public $item;

    /**
     * SettingsTreeItem constructor.
     * @param AbstractSettingsItem|AbstractSettingsGroup $item
     * @throws \Exception
     */
    public function __construct($item)
    {
        parent::__construct(new SettingsTreeValue($item));

        if ($item instanceof AbstractSettingsItem) {
            $this->graphic = Ide::getImage($item->getIcon(), [16, 16]);
        }

        $this->item = $item;
    }
}