<?php
namespace ide\l10n;

use framework\localization\Localizer;
use php\gui\framework\DataUtils;
use php\gui\UXLabeled;
use php\gui\UXMenu;
use php\gui\UXMenuBar;
use php\gui\UXMenuItem;
use php\gui\UXNode;
use php\gui\UXParent;
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
    /**
     * @param UXNode $node
     * @param array ...$args
     */
    public function translateNode(UXNode $node, ...$args)
    {
        if ($node instanceof UXLabeled) {
            $text = $node->text;

            $node->text = $this->translatePattern($text, ...$args);
            $this->bind('change-language', function () use ($node, $args, $text) {
                $node->text = $this->translatePattern($text, ...$args);
            });
        } else if ($node instanceof UXTextInputControl) {
            $text = $node->text;
            $promtText = $node->promptText;

            $node->text = $this->translatePattern($text, ...$args);
            $node->promptText = $this->translatePattern($promtText, ...$args);

            $this->bind('change-language', function () use ($node, $args, $text, $promtText) {
                $node->text = $this->translatePattern($text, ...$args);
                $node->promptText = $this->translatePattern($promtText, ...$args);
            });
        } else if ($node instanceof UXMenuBar) {
            /** @var UXMenu $menu */
            foreach ($node->menus as $menu) {
                $this->translateMenu($menu, ...$args);
            }
        } else if ($node instanceof UXTabPane) {
            foreach ($node->tabs as $tab) {
                $text = $tab->text;
                $tab->text = $this->translatePattern($tab->text, ...$args);

                $this->bind('change-language', function () use ($tab, $args, $text) {
                    $tab->text = $this->translatePattern($text, ...$args);
                });
            }
        }

        if ($node instanceof UXParent) {
            DataUtils::scanAll($node, function ($_, $node) use ($args) {
                $this->translateNode($node, ...$args);
            });
        }
    }

    public function translateMenu(UXMenu $menu, ...$args)
    {
        $text = $menu->text;
        $menu->text = $this->translatePattern($text);

        $this->bind('change-language', function () use ($menu, $args, $text) {
            $menu->text = $this->translatePattern($text, ...$args);
        });

        foreach ($menu->items as $item) {
            if ($item instanceof UXMenuItem) {
                $text = $item->text;
                $item->text = $this->translatePattern($item->text, ...$args);

                $this->bind('change-language', function () use ($item, $args, $text) {
                    $item->text = $this->translatePattern($text, ...$args);
                });
            } else if ($item instanceof UXMenu) {
                $this->translateMenu($item, ...$args);
            }
        }
    }

    public function translatePattern(string $text, ...$args)
    {
        if (!str::contains($text, '{')) {
            return $text;
        }

        $regex = new Regex('(\\{.+\\})', '', $text);
        return $regex->replaceWithCallback(function (Regex $regex) use ($args) {
            $text = $regex->group(1);
            return $this->translate(substr($text, 1, -1), $args);
        });
    }
}