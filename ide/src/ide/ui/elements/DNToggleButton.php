<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXToggleButton;

class DNToggleButton extends UXToggleButton
{
    /**
     * DNToggleButton constructor.
     */
    public function __construct() {
        parent::__construct();
        DNToggleButton::applyIDETheme($this);
    }

    /**
     * @param UXToggleButton $button
     */
    public static function applyIDETheme(UXToggleButton $button) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($button, $currentTheme->getCSSStyle()->getButtonCSS());
    }
}
