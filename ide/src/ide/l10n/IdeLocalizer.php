<?php
namespace ide\l10n;

use function alert;
use framework\core\Event;
use framework\localization\Localizer;
use ide\Ide;
use ide\Logger;
use php\gui\designer\UXDesignProperties;
use php\gui\framework\DataUtils;
use php\gui\UXChoiceBox;
use php\gui\UXComboBox;
use php\gui\UXComboBoxBase;
use php\gui\UXLabeled;
use php\gui\UXListView;
use php\gui\UXMenu;
use php\gui\UXMenuBar;
use php\gui\UXMenuItem;
use php\gui\UXNode;
use php\gui\UXParent;
use php\gui\UXTab;
use php\gui\UXTabPane;
use php\gui\UXTextInputControl;
use php\gui\UXTooltip;
use php\lib\str;
use php\util\Regex;
use function pre;


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
     * @param UXDesignProperties $properties
     * @return UXDesignProperties
     */
    public function translateDesignProperties(UXDesignProperties $properties)
    {
        $panes = $properties->getGroupPanes();
        foreach ($panes as $pane) {
            $pane = $this->translateNode($pane);
        }

        $properties->setPropertyNameGetter(function ($name) {
            return $this->translate($name);
        });

        if ($l10nBind = $properties->{'l10n-bind-id'}) {
            $this->off('after-change-language', $l10nBind);
        }

        $l10nBind = $this->bind('after-change-language', function () use ($properties, $panes) {
            $properties->update();
        });

        $properties->{'l10n-bind-id'} = $l10nBind;

        return $properties;
    }

    /**
     * @param UXTooltip $tooltip
     * @param array ...$args
     * @return UXTooltip
     * @internal param UXTooltip $node
     */
    public function translateTooltip(UXTooltip $tooltip, ...$args): UXTooltip
    {
        $text = $tooltip->text;

        $tooltip->text = $this->translate($text, $args);

        if ($l10nBind = $tooltip->data('l10n-bind-id')) {
            $this->off('after-change-language', $l10nBind);
        }

        $l10nBind = $this->bind('after-change-language', function () use ($tooltip, $args, $text) {
            $tooltip->text = $this->translate($text, $args);
        });

        $tooltip->data('l10n-bind-id', $l10nBind);
        return $tooltip;
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

            if ($tooltip) {
                $node->tooltipText = $this->translate($tooltip, $args);
            }

            if ($l10nBind = $node->data('l10n-bind-id')) {
                $this->off('after-change-language', $l10nBind);
            }

            $l10nBind = $this->bind('after-change-language', function () use ($node, $args, $text, $tooltip) {
                $node->text = $this->translate($text, $args);

                if ($tooltip) {
                    $node->tooltipText = $this->translate($tooltip, $args);
                }
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
        } else if ($node instanceof UXComboBox || $node instanceof UXChoiceBox) {
            $items = flow($node->items)->toArray();

            $selected = $node->selectedIndex;
            foreach ($items as $i => $item) {
                $node->items[$i] = $this->translate($item, $args);
            }
            $node->selectedIndex = $selected;

            if ($l10nBind = $node->data('l10n-bind-id')) {
                $this->off('after-change-language', $l10nBind);
            }

            $l10nBind = $this->bind('after-change-language', function () use ($node, $args, $items) {
                $selected = $node->selectedIndex;
                $newItems = [];

                foreach ($items as $i => $item) {
                    $newItems[$i] = $this->translate($item, $args);
                }

                $node->items->setAll($newItems);
                $node->selectedIndex = $selected;
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

    public function translate($message, array $args = [])
    {
        if ($message instanceof LocalizedString) {
            return $message;
        }

        if (!str::startsWith($message, '{') && !str::endsWith($message, '}')) {
            if (str::contains($message, "::")) {
                [$message, $def] = str::split($message, '::', 2);

                $result = (string) $this->translate($message, $args);

                if ($this->getUseDefaultValuesForLang() === $this->language) {
                    if ($result === $message) {
                        foreach ($args as $i => $arg) {
                            $def = str::replace($def, "{{$i}}", $arg);
                        }

                        return new LocalizedString($def);
                    }
                }

                return new LocalizedString($result);
            }

            return new LocalizedString(parent::translate($message, $args));
        }

        $regex = new Regex('(\\{.+\\})', '', $message);
        return new LocalizedString($regex->replaceWithCallback(function (Regex $regex) use ($args) {
            $text = $regex->group(1);
            $localizedString = $this->translate(substr($text, 1, -1), $args);
            return Regex::quoteReplacement($localizedString);
        }));
    }

    public function load(string $lang, string $file)
    {
        $this->trigger(new Event('change-language', $this, null, ['value' => $this->language]));

        parent::load($lang, $file);

        $this->trigger(new Event('after-change-language', $this, null, ['value' => $this->language]));
    }
}