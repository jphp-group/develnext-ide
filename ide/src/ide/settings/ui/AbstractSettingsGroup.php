<?php

namespace ide\settings\ui;

use ide\Ide;
use ide\settings\Settings;
use ide\settings\SettingsContext;
use php\gui\layout\UXVBox;
use php\gui\UXHyperlink;
use php\gui\UXLabel;
use php\gui\UXNode;

abstract class AbstractSettingsGroup {

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * @return AbstractSettingsItem[]
     */
    abstract public function getItems(): array;

    /**
     * @param SettingsContext $context
     * @return UXNode
     * @throws \Exception
     */
    public function makeUi(SettingsContext $context): UXNode {
        $box = new UXVBox();
        $box->alignment = "CENTER";
        $box->spacing = 8;
        $box->classes->add("settings-group");

        $name = new UXLabel(_($this->getName()));
        $name->font = $name->font->withBold();
        $box->add($name);

        $box->add(new UXLabel(
            _($this->getDescription())
        ));

        foreach ($this->getItems() as $item) {
            $link = new UXHyperlink(
                _($item->getName()),
                $item->getIcon() ? Ide::getImage($item->getIcon(), [16, 16]) : null
            );

            $link->on("action", function () use ($item) {
                Ide::get()->getSettings()->open($item);
            });

            $box->add($link);
        }

        return $box;
    }
}