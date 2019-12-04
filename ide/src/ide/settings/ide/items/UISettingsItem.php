<?php


namespace ide\settings\ide\items;


use ide\commands\ChangeThemeCommand;
use ide\commands\theme\IDETheme;
use ide\Ide;
use ide\IdeLanguage;
use ide\l10n\LocalizedString;
use ide\settings\SettingsContext;
use ide\settings\ui\AbstractSettingsItem;
use php\gui\layout\UXVBox;
use php\gui\UXCheckbox;
use php\gui\UXComboBox;
use php\gui\UXImage;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\UXNode;
use php\gui\UXSeparator;
use php\lib\str;

class UISettingsItem extends AbstractSettingsItem {

    /**
     * @return string
     */
    public function getName(): string {
        return "settings.ide.ui.item";
    }

    /**
     * @return string
     */
    public function getIcon(): string {
        return "icons/label16.png";
    }

    /**
     * @param SettingsContext $context
     * @return UXNode
     * @throws \Exception
     */
    public function makeUi(SettingsContext $context): UXNode {
        $box = new UXVBox();
        $box->spacing = 8;
        $box->padding = 16;

        // theme start

        $box->add(new UXLabel("settings.ide.ui.theme.title"));

        $themeCombobox = new UXComboBox();
        $themeCombobox->id = "theme-combobox";

        $themes = ChangeThemeCommand::$instance->getThemes();
        $currentTheme = ChangeThemeCommand::$instance->getCurrentTheme()->getName();
        $themeCombobox->items->clear();
        foreach ($themes as $key => $theme) {
            $themeCombobox->items->add($theme->getName());

            if ($theme->getName() == $currentTheme)
                $themeCombobox->selectedIndex = $key;
        }

        $box->add($themeCombobox);

        // theme end

        $box->add(new UXSeparator());

        // lang start

        $box->add(new UXLabel("settings.ide.ui.language.title"));

        $langCombobox = new UXComboBox();
        $langCombobox->id = "lang-combobox";
        $langCombobox->onCellRender([$this, "uiLanguageRender"]);
        $langCombobox->onButtonRender([$this, "uiLanguageRender"]);

        $currentLang = Ide::get()->getUserConfigValue('ide.language');
        $languages = Ide::get()->getLanguages();
        $langCombobox->items->clear();

        $index = 0;
        foreach ($languages as $code => $language) {

            /** @var IdeLanguage $language **/
            $langCombobox->items->add($code);

            if($code == $currentLang){
                $langCombobox->selectedIndex = $index;
            }

            $index++;
        }

        $box->add($langCombobox);

        // lang end

        $box->add(new UXSeparator());

        // splash start

        $box->add(new UXLabel("settings.ide.ui.splash.title"));

        $splashCheckBox = new UXCheckbox("settings.ide.ui.splash.show");
        $splashCheckBox->id = "splash-checkbox";
        $splashCheckBox->selected = Ide::get()->getUserConfigValue('ide.splash', 1);

        $box->add($splashCheckBox);

        // splash end

        return $box;
    }

    /**
     * @param SettingsContext $context
     * @throws \Exception
     */
    public function doSave(SettingsContext $context, UXNode $ui) {
        Ide::get()->setUserConfigValue('ide.splash', $ui->lookup("#splash-checkbox")->selected ? 1 : 0);

        ChangeThemeCommand::$instance->setCurrentTheme(
            $ui->lookup("#theme-combobox")->value->__toString());
        ChangeThemeCommand::$instance->onExecute();


        $langCode = $ui->lookup("#lang-combobox")->value->__toString();
        if(isset(Ide::get()->getLanguages()[$langCode])){
            Ide::get()->setUserConfigValue('ide.language', $langCode);
            Ide::get()->getLocalizer()->language = $langCode;
        }
    }

    /**
     * @param SettingsContext $context
     * @return bool
     * @throws \Exception
     */
    public function canSave(SettingsContext $context, UXNode $ui): bool {
        $theme = str::lower($ui->lookup("#theme-combobox")->value->__toString())
            != str::lower(ChangeThemeCommand::$instance->getCurrentTheme());

        $lang = $ui->lookup("#lang-combobox")->value->__toString()
            != Ide::get()->getUserConfigValue('ide.language');

        $splash = $ui->lookup("#splash-checkbox")->selected
            != Ide::get()->getUserConfigValue('ide.splash', 1);

        return $theme || $lang || $splash;
    }

    // helpers

    /**
     * @param UXListCell $item
     * @param LocalizedString $value
     * @throws \Exception
     */
    public function uiLanguageRender(UXListCell $item, $value){
        $language = Ide::get()->getLanguages()[(string) $value];

        $item->text = $language->getTitle();
        $item->graphic = Ide::get()->getImage(new UXImage($language->getIcon()));

        if ($language->getTitle() != $language->getTitleEn()) {
            $item->text .= ' (' . $language->getTitleEn() . ')';
        }

        if ($language->isBeta()) {
            $item->text .= ' (Beta Version)';
        }
    }

    /**
     * @param  UXListCell $item
     * @param  string $value
     */
    public function uiThemeRender(UXListCell $item, $value){
        $item->text = $value->__toString();
    }
}