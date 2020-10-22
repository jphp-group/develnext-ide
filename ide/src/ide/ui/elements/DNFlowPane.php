<?php


namespace ide\ui\elements;


use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\layout\UXFlowPane;

class DNFlowPane extends UXFlowPane
{
    /**
     * DNFlowPane constructor.
     */
    public function __construct() {
        parent::__construct();
        DNListView::applyIDETheme($this);
    }

    /**
     * @param UXFlowPane $flowPane
     */
    public static function applyIDETheme(UXFlowPane $flowPane) {
        DNAnchorPane::applyIDETheme($flowPane);

        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($flowPane, $currentTheme->getCSSStyle()->getFlowPaneCSS());
    }
}
