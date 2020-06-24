<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXNode;
use php\gui\UXTitledPane;

class DNTitledPane extends UXTitledPane {

    /**
     * DNTitledPane constructor.
     * @param $title
     * @param UXNode|null $content
     */
    public function __construct($title, UXNode $content = null)
    {
        parent::__construct($title, $content);
        DNTitledPane::applyIDETheme($this);
    }

    /**
     * @param UXTitledPane $pane
     */
    public static function applyIDETheme(UXTitledPane $pane) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        // FIXME: use different style
        CSSStyle::applyCSSToNode($pane, $currentTheme->getCSSStyle()->getButtonCSS());
    }
}
