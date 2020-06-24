<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXSeparator;

class DNSeparator extends UXSeparator
{
    /**
     * DNSeparator constructor.
     * @param string $orientation
     */
    public function __construct($orientation = 'HORIZONTAL') {
        parent::__construct($orientation);
        DNSeparator::applyIDETheme($this);
    }

    /**
     * @param UXSeparator $separator
     */
    public static function applyIDETheme(UXSeparator $separator) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($separator, $currentTheme->getCSSStyle()->getSeparatorCSS());
    }
}
