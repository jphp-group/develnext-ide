<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXCheckbox;

class DNCheckbox extends UXCheckbox {

    /**
     * DNCheckbox constructor.
     * @param $text
     */
    public function __construct($text) {
        parent::__construct($text);
        DNCheckbox::applyIDETheme($this);
    }

    /**
     * @param UXCheckbox $button
     */
    public static function applyIDETheme(UXCheckbox $button) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($button, $currentTheme->getCSSStyle()->getButtonCSS());
    }
}
