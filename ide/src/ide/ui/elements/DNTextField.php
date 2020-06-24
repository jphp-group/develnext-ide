<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXTextField;

class DNTextField extends UXTextField
{
    /**
     * DNTextField constructor.
     */
    public function __construct() {
        parent::__construct();
        DNTextField::applyIDETheme($this);
    }

    /**
     * @param UXTextField $field
     */
    public static function applyIDETheme(UXTextField $field) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($field, $currentTheme->getCSSStyle()->getTextInputCSS());
    }
}
