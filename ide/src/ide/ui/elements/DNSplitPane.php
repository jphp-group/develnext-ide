<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXSplitPane;

class DNSplitPane extends UXSplitPane {

    /**
     * DNSplitPane constructor.
     * @param array $items
     */
    public function __construct(array $items = []) {
        parent::__construct($items);
        DNSplitPane::applyIDETheme($this);
    }

    /**
     * @param UXSplitPane $pane
     */
    public static function applyIDETheme(UXSplitPane $pane) {
        $pane->classes->add("dn-split-pane");

        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($pane, $currentTheme->getCSSStyle()->getSplitPaneCSS());
    }
}
