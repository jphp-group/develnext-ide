<?php

namespace ide\forms;

use ide\Ide;
use ide\settings\SettingsContext;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;
use ide\ui\SettingsTreeItem;
use php\gui\layout\UXAnchorPane;
use php\gui\UXSplitPane;
use php\gui\UXTreeItem;
use php\gui\UXTreeView;
use php\lib\arr;

class SettingsForm extends AbstractIdeForm
{
    /**
     * @var SettingsTreeItem
     */
    private $treeId;

    /**
     * @var UXTreeView
     */
    private $tree;

    private $root;

    /**
     * SettingsForm constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $split = new UXSplitPane();
        $split->dividerPositions = [ .3 ];
        $this->tree = new UXTreeView();
        $this->tree->rootVisible = false;
        $this->tree->root = new UXTreeItem();

        foreach (Ide::get()->getSettings()->getSettingGroups() as $group) {
            $groupTreeItem = new SettingsTreeItem($group);
            $this->tree->root->children->add($groupTreeItem);

            $this->treeId[get_class($group)] = $groupTreeItem;

            /** @var SettingsTreeItem $child */
            foreach ($group->getItems() as $item) {
                $treeItem = new SettingsTreeItem($item);
                $groupTreeItem->children->add($treeItem);
                $this->treeId[get_class($item)] = $treeItem;
            }
        }

        $split->items->add($this->tree);
        $split->items->add($this->root = new UXAnchorPane());

        $this->layout = $split;

        $this->tree->on("click", function () {
            if (arr::count($this->tree->selectedItems) > 0) {
                static $item;

                if ($item == $this->tree->selectedItems[0]->value->getItem()) return;
                $item = $this->tree->selectedItems[0]->value->getItem();

                $this->open($item);
            }
        });
    }

    private function focusTreeItem($item) {
        $this->tree->selectedItems = [ $this->treeId[get_class($item)] ];
        $this->tree->focusedItem = $this->treeId[get_class($item)];
    }

    /**
     * @param AbstractSettingsItem|AbstractSettingsGroup $item
     * @throws \Exception
     */
    public function open($item) {
        $this->focusTreeItem($item);

        $ui = _($item->makeUi(new SettingsContext($item->getName())));

        UXAnchorPane::setAnchor($ui, 0);

        $this->root->children->clear();
        $this->root->add($ui);
    }
}
