<?php

namespace php\gui\monaco;

use php\gui\layout\UXRegion;
use php\gui\UXContextMenu;

class MonacoEditor extends UXRegion {

    /**
     * @var UXContextMenu
     */
    public ?UXContextMenu $contextMenu = null;

    /**
     * @return Editor
     */
    public function getEditor(): Editor {
    }

    /**
     * @param callable $onLoad
     */
    public function setOnLoad(callable $onLoad)
    {
    }
}
