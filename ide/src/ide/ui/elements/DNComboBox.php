<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXComboBox;
use php\gui\UXComboBoxBase;

class DNComboBox extends UXComboBox
{
    /**
     * DNComboBox constructor.
     * @param $items
     */
    public function __construct($items = []) {
        parent::__construct($items);
        DNComboBox::applyIDETheme($this);
    }

    /**
     * @param UXComboBoxBase $box
     */
    public static function applyIDETheme(UXComboBoxBase $box) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($box, $currentTheme->getCSSStyle()->getButtonCSS());
    }
}
