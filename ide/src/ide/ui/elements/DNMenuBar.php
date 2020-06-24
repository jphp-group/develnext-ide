<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXMenuBar;

class DNMenuBar extends UXMenuBar
{
    /**
     * DNMenuBar constructor.
     */
    public function __construct()
    {
        parent::__construct();
        DNMenuBar::applyIDETheme($this);
    }

    /**
     * @param UXMenuBar $bar
     */
    public static function applyIDETheme(UXMenuBar $bar) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($bar, $currentTheme->getCSSStyle()->getMenuBarCSS());
    }
}
