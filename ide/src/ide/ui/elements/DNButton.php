<?php

namespace ide\ui\elements;

use Exception;
use ide\commands\ChangeThemeCommand;
use ide\commands\theme\ColorPalette;
use ide\commands\theme\IDETheme;
use php\gui\UXButton;
use php\gui\UXNode;

class DNButton extends UXButton
{
    /**
     * DNButton constructor.
     * @param string $text
     * @param UXNode|null $graphic
     * @throws Exception
     */
    public function __construct($text = '', UXNode $graphic = null)
    {
        parent::__construct($text, $graphic);
        DNButton::applyIDETheme($this);
    }

    /**
     * @param UXButton $button
     */
    public static function applyIDETheme(UXButton $button) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        ColorPalette::applyCSSToNode($button, $currentTheme->getColorPalette()->getButtonCSS());
    }
}
