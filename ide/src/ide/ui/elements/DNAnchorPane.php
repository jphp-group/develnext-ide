<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\layout\UXPane;
use php\gui\layout\UXPanel;

class DNAnchorPane extends UXPanel {

    /**
     * DNAnchorPane constructor.
     */
    public function __construct() {
        parent::__construct();
        DNAnchorPane::applyIDETheme($this);
    }

    /**
     * @param UXPane $panel
     */
    public static function applyIDETheme(UXPane $panel) {
        $panel->classes->add("dn-anchor-pane");

        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($panel, $currentTheme->getCSSStyle()->getBoxPanelCSS());
    }
}
