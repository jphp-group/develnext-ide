<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXLabel;
use php\gui\UXNode;

class DNLabel extends UXLabel
{
    /**
     * DNLabel constructor.
     * @param string $text
     * @param UXNode|null $graphic
     */
    public function __construct($text = '', UXNode $graphic = null) {
        parent::__construct($text, $graphic);
        DNLabel::applyIDETheme($this);
    }

    /**
     * @param UXLabel $label
     */
    public static function applyIDETheme(UXLabel $label) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($label, $currentTheme->getCSSStyle()->getLabelCSS());
    }
}
