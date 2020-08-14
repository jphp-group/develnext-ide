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
     * MonacoEditor constructor.
     * @param string $htmlSource
     */
    public function __construct(string $htmlSource = '/eu/mihosoft/monacofx/monaco-editor-0.20.0/index.html')
    {
    }


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
