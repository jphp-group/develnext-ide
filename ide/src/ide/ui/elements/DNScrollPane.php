<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\layout\UXScrollPane;
use php\gui\UXNode;

class DNScrollPane extends UXScrollPane {

    /**
     * DNScrollPane constructor.
     * @param UXNode $node
     */
    public function __construct(UXNode $node = null) {
        parent::__construct($node);
        DNScrollPane::applyIDETheme($this);
    }

    /**
     * @param UXScrollPane $pane
     */
    public static function applyIDETheme(UXScrollPane $pane) {
        $pane->classes->add("dn-scroll-pane");

        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($pane, $currentTheme->getCSSStyle()->getScrollPaneCSS());
    }
}
