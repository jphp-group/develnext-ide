<?php
namespace ide\project\supports\jppm;

use ide\project\control\AbstractProjectControlPane;
use php\gui\UXLabel;
use php\gui\UXNode;

class JPPMControlPane extends AbstractProjectControlPane
{
    public function getName()
    {
        return 'jppm.package.manager';
    }

    public function getDescription()
    {
        return 'jppm.package.manager.description';
    }

    public function getIcon()
    {
        return 'icons/pluginEx16.png';
    }

    /**
     * @return UXNode
     */
    protected function makeUi()
    {
        return new UXLabel("Coming soon ...");
    }

    /**
     * Refresh ui and pane.
     */
    public function refresh()
    {
    }
}