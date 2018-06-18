<?php
namespace ide\l10n;

use framework\core\Event;
use framework\localization\Localizer;
use ide\Logger;
use php\gui\framework\DataUtils;
use php\gui\UXLabeled;
use php\gui\UXMenu;
use php\gui\UXMenuBar;
use php\gui\UXMenuItem;
use php\gui\UXNode;
use php\gui\UXParent;
use php\gui\UXTab;
use php\gui\UXTabPane;
use php\gui\UXTextInputControl;
use php\lib\str;
use php\util\Regex;

/**
 * Class IdeLocalizer
 * @package ide\l10n
 */
class IdeLocalizer extends Localizer
{
    private $useDefaultValuesForLang = null;

    /**
     * @return null
     */
    public function getUseDefaultValuesForLang()
    {
        return $this->useDefaultValuesForLang;
    }

    /**
     * @param null $useDefaultValuesForLang
     */
    public function setUseDefaultValuesForLang($useDefaultValuesForLang)
    {
        $this->useDefaultValuesForLang = $useDefaultValuesForLang;
    }

    /**
     * @param UXNode $node
     * @param array ...$args
     */
    public function translateNode(UXNode $node, ...$args)
    {
        if ($node instanceof UXLabeled) {
            $text = $node->text;
            $tooltip = $node->tooltipText;

            $node->text = $this->translate($text, $args);
            $node->tooltipText = $this->translate($tooltip, $args);

            if ($l10nBind = $node->data('l10n-bind-id')) {
                $this->off('after-change-language', $l10nBind);
            }

            $l10nBind = $this->bind('after-change-language', function () use ($node, $args, $text, $tooltip) {
                $node->text = $this->translate($text, $args);
                $node->tooltipText = $this->translate($tooltip, $args);
            });

            $node->data('l10n-bind-id', $l10nBind);

        } else if ($node instanceof UXTextInputControl) {
            $text = $node->text;
            $promptText = $node->promptText;

            $node->text = $this->translate($text, $args);
            $node->promptText = $this->translate($promptText, $args);

            if ($l10nBind = $node->data('l10n-bind-id')) {
                $this->off('after-change-language', $l10nBind);
            }

            $l10nBind = $this->bind('after-change-language', function () use ($node, $args, $text, $promptText) {
                $node->text = $this->translate($text, $args);
                $node->promptText = $this->translate($promptText, $args);
            });

            $node->data('l10n-bind-id', $l10nBind);
        } else if ($node instanceof UXMenuBar) {
            /** @var UXMenu $menu */
            foreach ($node->menus as $menu) {
                $this->translateMenu($menu, ...$args);
            }
        } else if ($node instanceof UXTabPane) {
            /** @var UXTab $tab */
            foreach ($node->tabs as $tab) {
                $this->translateTab($tab);
            }
        }

        if ($node instanceof UXParent) {
            DataUtils::scanAll($node, function ($_, $node) use ($args) {
                $this->translateNode($node, ...$args);
            });
        }
    }

    public function translateTab(UXTab $tab, ...$args)
    {
        $text = $tab->text;
        $tab->text = $this->translate($tab->text, $args);

        if ($l10nBind = $tab->data('l10n-bind-id')) {
            $this->off('after-change-language', $l10nBind);
        }

        $l10nBind = $this->bind('after-change-language', function () use ($tab, $args, $text) {
            $tab->text = $this->translate($text, $args);
        });

        $tab->data('l10n-bind-id', $l10nBind);
    }

    public function translateMenuItem(UXMenuItem $item, ...$args)
    {
        $text = $item->text;
        $item->text = $this->translate($item->text, $args);

        if ($l10nBind = $item->userData) {
            $this->off('after-change-language', $l10nBind);
        }

        $l10nBind = $this->bind('after-change-language', function () use ($item, $args, $text) {
            $item->text = $this->translate($text, $args);
        });

        $item->userData = $l10nBind;
    }

    public function translateMenu(UXMenu $menu, ...$args)
    {
        $text = $menu->text;
        $menu->text = $this->translate($text, $args);

        if ($l10nBind = $menu->userData) {
            $this->off('after-change-language', $l10nBind);
        }

        $l10nBind = $this->bind('after-change-language', function () use ($menu, $args, $text) {
            $menu->text = $this->translate($text, $args);
        });

        foreach ($menu->items as $item) {
            if ($item instanceof UXMenuItem) {
                $this->translateMenuItem($item);
            } else if ($item instanceof UXMenu) {
                $this->translateMenu($item, ...$args);
            }
        }

        $menu->userData = $l10nBind;
    }

    /*public function translatePattern(string $text, ...$args)
    {
        if (!str::contains($text, '{')) {
            return $text;
        }

        $regex = new Regex('(\\{.+\\})', '', $text);
        return $regex->replaceWithCallback(function (Regex $regex) use ($args) {
            $text = $regex->group(1);
            return $this->translate(substr($text, 1, -1), $args);
        });
    }*/

    public function translate($message, array $args = []): string
    {
        if (!str::contains($message, '{')) {
            if (str::contains($message, "::")) {
                [$message, $def] = str::split($message, '::', 2);

                $result = $this->translate($message, $args);

                if ($this->getUseDefaultValuesForLang() === $this->language) {
                    if ($result === $message) {
                        return $def;
                    }
                }

                return $result;
            }

            return parent::translate($message, $args);
        }

        $regex = new Regex('(\\{.+\\})', '', $message);
        return $regex->replaceWithCallback(function (Regex $regex) use ($args) {
            $text = $regex->group(1);
            return $this->translate(substr($text, 1, -1), $args);
        });
    }

    public function load(string $lang, string $file)
    {
        $this->trigger(new Event('change-language', $this, null, ['value' => $this->language]));

        parent::load($lang, $file);

        $this->trigger(new Event('after-change-language', $this, null, ['value' => $this->language]));
    }
}