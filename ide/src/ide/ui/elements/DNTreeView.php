<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXTreeView;

class DNTreeView extends UXTreeView {

    /**
     * DNTreeView constructor.
     */
    public function __construct() {
        parent::__construct();

        DNTreeView::applyIDETheme($this);
    }

    /**
     * @param UXTreeView $tree
     */
    public static function applyIDETheme(UXTreeView $tree) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($tree, $currentTheme->getCSSStyle()->getTreeViewCSS());
    }
}
