<?php

namespace ide\settings\ide\items;

use ide\Ide;
use ide\settings\SettingsContext;
use ide\settings\ui\AbstractSettingsItem;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXButton;
use php\gui\UXLabel;
use php\gui\UXListView;
use php\gui\UXNode;
use php\gui\UXSplitPane;

class PluginsSettingsItem extends AbstractSettingsItem {

    /**
     * @return string
     */
    public function getName(): string {
        return "settings.ide.plugins";
    }

    /**
     * @return string
     */
    public function getIcon(): string {
        return "icons/plugin16.png";
    }

    /**
     * @param SettingsContext $context
     * @return UXNode
     * @throws \Exception
     */
    public function makeUi(SettingsContext $context): UXNode {
        $list = new UXListView();
        $mainBox = new UXSplitPane();
        $extensions = Ide::get()->getExtensions();

        $mainBox->items->add($list);

        foreach ($extensions as $extension) {
            $label = new UXLabel(_($extension->getName()));
            $label->font = $label->font->withBold();

            $box = new UXHBox([
                Ide::getImage($extension->getIcon32(), [32, 32]),
                $label
            ], 8);

            $fullBox = new UXVBox();
            $fullBox->spacing =
            $fullBox->padding = 8;

            $header = new UXHBox([
                Ide::getImage($extension->getIcon32(), [32, 32]),
                new UXLabel(_($extension->getName()))
            ], 8);

            $header->alignment = "CENTER_LEFT";
            $fullBox->add($header);

            $description = new UXLabel($extension->getDescription());
            $description->wrapText = true;

            $fullBox->add($description);

            $buttonsBox = new UXHBox([
                _($disableButton = new UXButton("plugin.disable")),
                _($removeButton = new UXButton("plugin.remove")),
            ], 8);

            $fullBox->add($buttonsBox);

            $removeButton->enabled  =
            $disableButton->enabled = !$extension->isSystem();

            $box->on("click", function () use ($mainBox, $box, $fullBox) {
                if ($mainBox->items->count() == 1) {
                    $mainBox->items->add(_($fullBox));
                } else $mainBox->items->set(1, _($fullBox));
            });

            $box->alignment = "CENTER_LEFT";

            $list->items->add($box);
        }

        return _($mainBox);
    }

    public function showBottomButtons(): bool {
        return false;
    }

    /**
     * @param SettingsContext $context
     * @param UXNode $ui
     */
    public function doSave(SettingsContext $context, UXNode $ui) {
        // ignore
    }

    /**
     * @param SettingsContext $context
     * @param UXNode $ui
     * @return bool
     */
    public function canSave(SettingsContext $context, UXNode $ui): bool {
        // ignore
    }
}