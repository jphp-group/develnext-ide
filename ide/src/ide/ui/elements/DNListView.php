<?php

namespace ide\ui\elements;

use ide\commands\ChangeThemeCommand;
use ide\commands\theme\CSSStyle;
use ide\commands\theme\IDETheme;
use php\gui\UXListView;

class DNListView extends UXListView {

    /**
     * DNListView constructor.
     */
    public function __construct() {
        parent::__construct();
        DNListView::applyIDETheme($this);
    }

    /**
     * @param UXListView $list
     */
    public static function applyIDETheme(UXListView $list) {
        /** @var IDETheme $currentTheme */
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme();
        CSSStyle::applyCSSToNode($list, $currentTheme->getCSSStyle()->getListViewCSS());
    }
}
