<?php
namespace ide\forms;

use ide\Ide;
use ide\IdeLanguage;
use ide\Logger;
use ide\forms\AbstractIdeForm;
use php\gui\UXImage;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\paint\UXColor;
use php\lang\JavaException;


/**
 * @property UXLabel $splashLabel
 * @property UXCheckBox $splashShowCheck
 * @property UXLabel $langLabel
 * @property UXCombobox $langCombo
 * @property UXLabel $versionLabel
 * @property UXButton $saveButton
 */
class SettingsForm extends AbstractIdeForm
{	
    /**
     * @event show
     */
    public function doShow()
    {
        Logger::info("Show settings form ...");
        $this->loadConfigs();

    	$this->langCombo->onButtonRender([$this, 'uiLanguageRender']); // Отрисовка выбранного элемента
    	$this->langCombo->onCellRender([$this, 'uiLanguageRender']); // Отрисовка выпадающего списка
        $this->saveButton->on('action', [$this, 'doSave']);
    }

    /**
     * Загрузка параметров из конфигов
     */
    protected function loadConfigs(){
    	// Отображение сплеша
    	$showSplash = Ide::get()->getUserConfigValue('ide.splash', 1);
    	$this->splashShowCheck->selected = (intval($showSplash) == 1);

    	// Версия приложения
    	$appName = app()->getConfig()->get('app.name');
    	$appVer = app()->getConfig()->get('app.version');
    	$appCode = app()->getConfig()->get('app.versionCode');
    	$appIsSnap = intval(app()->getConfig()->get('app.versionSnapshot')) == 1;
    	$this->versionLabel->text = "$appName $appVer-$appCode" . ($appIsSnap ? ' (SNAPSHOT)' : '');

    	// Загрузка списка языков
    	$currentLang = Ide::get()->getUserConfigValue('ide.language');
    	$languages = Ide::get()->getLanguages();
    	$index = 0;
    	foreach ($languages as $code => $language) {
    		/** @var IdeLanguage $language **/
            $this->langCombo->items->add($code);

            if($code == $currentLang){
            	$this->langCombo->selectedIndex = $index;
            }
            $index++;
    	}
    }

    /**
     * Преобразует текстовое значение кода языка в label + флаг языка
     * @param  UXListCell $item  Ячейка в comboBox
     * @param  string     $value Значение ячейки
     */
    public function uiLanguageRender(UXListCell $item, $value){
    	$language = Ide::get()->getLanguages()[$value];

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
     * Сохранение параметров и закрытие формы
     */
    public function doSave(){
    	$this->hide();

    	// Параметры сплеша
    	Ide::get()->setUserConfigValue('ide.splash', $this->splashShowCheck->selected ? 1 : 0);

    	// Параметры языка
    	$langCode = $this->langCombo->selected;
    	if(isset(Ide::get()->getLanguages()[$langCode])){
    		Ide::get()->setUserConfigValue('ide.language', $langCode);
        	Ide::get()->getLocalizer()->language = $langCode;
    	}
    }
}
