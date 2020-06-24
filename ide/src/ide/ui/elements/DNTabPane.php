<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXTabPane;

class DNTabPane extends UXTabPane
{
    /**
     * DNTabPane constructor.
     */
    public function __construct() {
        parent::__construct();
        DNTabPane::applyIDETheme($this);
    }

    /**
     * @param UXTabPane $tabs
     */
    public static function applyIDETheme(UXTabPane $tabs) {
        $tabs->classes->add("dn-tab-pane");

        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($tabs, $currentTheme->getCSSStyle()->getTabPaneCSS());
    }
}
