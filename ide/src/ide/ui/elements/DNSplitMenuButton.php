<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXNode;
use php\gui\UXSplitMenuButton;

class DNSplitMenuButton extends UXSplitMenuButton
{
    /**
     * DNSplitMenuButton constructor.
     * @param null $text
     * @param UXNode|null $graphic
     */
    public function __construct($text = null, UXNode $graphic = null) {
        parent::__construct($text, $graphic);
        DNSplitMenuButton::applyIDETheme($this);
    }

    /**
     * @param UXSplitMenuButton $button
     */
    public static function applyIDETheme(UXSplitMenuButton $button) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($button, $currentTheme->getCSSStyle()->getButtonCSS());
    }
}
