<?php
namespace ide\project\supports\jppm;

use ide\Ide;
use ide\Logger;
use ide\account\api\ServiceResponse;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\project\control\AbstractProjectControlPane;
use ide\project\supports\JPPMProjectSupport;
use php\gui\UXAlert;
use php\gui\UXButton;
use php\gui\UXClipboard;
use php\gui\UXContextMenu;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXListView;
use php\gui\UXMenuItem;
use php\gui\UXNode;
use php\gui\UXProgressBar;
use php\gui\UXTextField;
use php\gui\event\UXKeyEvent;
use php\gui\event\UXMouseEvent;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\layout\UXPanel;
use php\gui\layout\UXVBox;
use php\gui\paint\UXColor;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;


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
        return 'icons/plugin16.png';
    }

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var JPPMProjectSupport
     */
    protected $jppm;

    /**
     * @var JPPMPackageFileTemplate
     */
    protected $packageTpl;

    /**
     * @var UXListView
     */
    protected $packagesList;

    /**
     * @var UXListView
     */
    protected $repoList;

    /**
     * @var UXTextField
     */
    protected $nameField;

    /**
     * @var UXTextField
     */
    protected $versionField;

    /**
     * @var UXButton
     */
    protected $delButton;

    /**
     * @var UXButton
     */
    protected $readmeButton;

    /**
     * @var UXButton
     */
    protected $addButton;

    /**
     * @var UXButton
     */
    protected $repoUpdateButton;

    /**
     * @var UXVBox
     */
    protected $parentPane;

    /**
     * @var UXVBox
     */
    protected $paginationBox;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * Создание графического интерфейса
     * @return UXNode
     */
    protected function makeUi()
    {   
        $this->project = Ide::project();

        if($this->project->hasSupport('jppm')){
            $this->jppm = $this->project->findSupport('jppm');
            $this->packageTpl = $this->jppm->getPkgTemplate();
        }

        // Основа - вертикальный бокс
        $this->parentPane = new UXVBox;
        $this->parentPane->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => 0];
        $this->parentPane->padding = 3;
        $this->parentPane->spacing = 8;      
        
        $packagesLabel = new UXLabel(_('jppm.package.manager.packages.title'));
        $packagesLabel->font = $packagesLabel->font->withBold()->withSize(16);
        $this->parentPane->add($packagesLabel);

        // 1. Список с установленными расширениями + кнопки удалить/readme
        $packBox = new UXHBox;
        $packBox->spacing = 5;  
        $this->parentPane->add($packBox);
        
        // 1.1 Список
        $this->packagesList = new UXListView;
        $this->packagesList->on('action', [$this, 'doSelectPackage']);
        UXHBox::setHgrow($this->packagesList, 'ALWAYS');
        $packBox->add($this->packagesList);
        $this->uiPackageContextMenu($this->packagesList);
        
        // 1.2 Бокс для кнопок
        $buttonsBox = new UXVBox;
        $buttonsBox->spacing = 5;  
        $packBox->add($buttonsBox);
        
        // 1.2.1 Кнопка удалить
        $this->delButton = new UXButton(_('jppm.package.manager.delete'), ico('close16'));
        $this->delButton->enabled = false;
        $this->delButton->width = 150;
        $this->delButton->height = 32;
        $this->delButton->on('click', [$this, 'doDeletePackage']);
        $buttonsBox->add($this->delButton);
        
        // 1.2.2 Кнопка readme
        $this->readmeButton = new UXButton(_('jppm.package.manager.readme'), ico('search16'));
        $this->readmeButton->enabled = false;
        $this->readmeButton->width = 150;
        $this->readmeButton->height = 32;
        $this->readmeButton->on('click', [$this, 'doBrowseReadme']);
        $buttonsBox->add($this->readmeButton);
        
        // 2. Установка пакетов из репозитория         
        $addLabel = new UXLabel(_('jppm.package.manager.addpane.title'));
        $addLabel->font = $packagesLabel->font->withBold()->withSize(16);
        $this->parentPane->add($addLabel);

        $addPane = new UXPanel;
        $addPane->style = '-fx-background-color: -fx-background';
        $addPane->padding = 10;
        $this->parentPane->add($addPane);  
        
        $addPaneBox = new UXVBox;
        $addPaneBox->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => 0];
        $addPaneBox->spacing = 10;
        $addPane->add($addPaneBox);
              
        // 2.1 Бокс с добавлением нового пакета
        $addBox = new UXHBox;
        $addBox->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => false];
        $addBox->spacing = 5;
        $addPaneBox->add($addBox);
        
        // 2.1.1 Поле ввода: имя пакета
        $this->nameField = new UXTextField;
        $this->nameField->promptText = _('jppm.package.manager.name.placeholder');
        $this->nameField->on('keyUp', function(UXKeyEvent $e){
            if($e->codeName == 'Enter'){
                // Нажатие enter == нажатие на кнопку добавить
                $this->doAddPackage();
            } elseif($e->codeName == 'Shift' || $e->codeName == 'Ctrl' || $e->codeName == '2'){
                // Если нажать ctrl+v или shift+2 (== @) и если была вставлена команда вида packsearch16age@source, то распарсим эту строку и разбросаем данные по полям
                if(str::pos($this->nameField->text, '@') > -1){
                    // Вместе с названием репозитория может быть скопирована команда jppm
                    $this->nameField->text = str::replace($this->nameField->text, 'jppm add ', '');
                    if(str::endsWith($this->nameField->text, '@')){
                        // Принажатии на @ перебрасывает на следующее поле
                        $this->nameField->text = str::replace($this->nameField->text, '@', '');
                    } else {
                        $exp = str::split($this->nameField->text, '@', 2);
                        $this->nameField->text = $exp[0];
                        $this->versionField->text = $exp[1];
                    }
                    $this->versionField->requestFocus();
                }
            }
        });
        UXHBox::setHgrow($this->nameField, 'ALWAYS');
        $addBox->add($this->nameField);
        
        // 2.1.2 Знак @
        $aLabel = new UXLabel('@');
        $aLabel->font = $aLabel->font->/*withBold()->*/withSize(16);
        $aLabel->textColor = UXColor::of('#666');
        $addBox->add($aLabel);

        // 2.1.3 Поле ввода: версия пакета
        $this->versionField = new UXTextField;
        $this->versionField->promptText = _('jppm.package.manager.version.placeholder');
        $this->versionField->maxWidth = 350;
        $this->versionField->on('keyUp', function(UXKeyEvent $e){
            if($e->codeName == 'Enter'){
                $this->doAddPackage();
            }
        });
        $addBox->add($this->versionField);
        
        // 2.1.4 Кнопка добавить пакет
        $this->addButton = new UXButton(_('jppm.package.manager.add'), ico('add16'));
        $this->addButton->width = 140;
        $this->addButton->on('click', [$this, 'doAddPackage']);
        $addBox->add($this->addButton);
        
        
        // 2.2 Текст репозиторий
        $repoLabel = new UXLabel(_('jppm.package.manager.repo'), ico('database16'));
        $repoLabel->font = $repoLabel->font->withBold();
        $addPaneBox->add($repoLabel);
        
        // 2.3 Список репозитория
        $this->repoList = new UXListView;
        $this->repoList->on('action', [$this, 'doSelectRepo']);
        $this->repoList->on('click', function(UXMouseEvent $e){
            if ($e->isDoubleClick() && $this->repoList->selectedIndex > -1) {
                $this->doAddPackage();
            }
        });
        $addPaneBox->add($this->repoList);
        $this->uiPackageContextMenu($this->repoList);
        
        // 2.4 Кнопка обновить репозиторий и пагинация
        $this->repoUpdateButton = new UXButton(_('jppm.package.manager.refresh'), ico('refresh16'));
        $this->repoUpdateButton->on('action', [$this, 'doUpdateRepos']);

        $buildPaginationButton = function ($icon, $offset): UXButton {
            $button = new UXButton();
            $button->graphic = ico($icon);
            $button->on("action", function () use ($offset) {
                $this->offset += $offset;
                $this->doUpdateRepos();
            });

            return $button;
        };

        $this->paginationBox = new UXHBox();
        $this->paginationBox->spacing = 8;
        $this->paginationBox->add($buildPaginationButton("undo16", -30));
        $this->paginationBox->add($buildPaginationButton("redo16", 30));

        $addPaneBox->add(new UXHBox([
            $this->repoUpdateButton, $this->paginationBox
        ], 8));

        return $this->parentPane;
    }

    /**
     * Прелоадер при установке пакета расширений
     */
    protected function createPreloader(): UXNode {
        $progressBar = new UXProgressBar;
        $progressBar->progress = -1;
        $progressBar->height = 24;
        $progressBar->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => false];

        $progressPane = new UXAnchorPane;
        $progressPane->add($progressBar);
        UXHBox::setHgrow($progressBar, 'ALWAYS');
        
        return $progressPane;
    }

    /**
     * Обновление содержимого панели
     */
    public function refresh() {
        if(is_null($this->packageTpl)) return;

        $this->disableUI();
        $this->doUpdatePackages();     
        $this->doUpdateRepos();
        $this->enableUI();
    }

    /**
     * Обновить список используемых в текущем проекте пакетов
     */
    public function doUpdatePackages(){
        $this->packagesList->items->clear();
        $this->packageTpl->load();
        $packages = $this->packageTpl->getDeps();

        foreach ($packages as $name => $version){
            $packageData = $this->jppm->getDepConfig($name);
            $isBundle = isset($packageData['ide-bundle']);

            $nameText = (isset($packageData['name']) && (str::length($packageData['name']) > 0)) ? $packageData['name'] : $name;
            $nameLabel = new UXLabel($nameText);
            $nameLabel->textColor = UXColor::of('#000000');
            $nameLabel->font = $nameLabel->font->withBold();

            $versionLabel = new UXLabel('@' . $version);
            $versionLabel->textColor = UXColor::of('#333333');

            $nameBox = new UXHBox([$nameLabel, $versionLabel]);

            $descript = $packageData['description'] ?? $packageData['develnext-bundle']['description'] ?? _('jppm.package.manager.no-description');
            $descriptLabel = new UXLabel($descript);
            $descriptLabel->font = $descriptLabel->font->withSize(11);
            $descriptLabel->textColor = UXColor::of('#333333');

            $textBox = new UXVBox([$nameBox, $descriptLabel]);

            // Разные иконки для пакетов jphp и bundle
            if($isBundle){
                if(isset($packageData['ide-bundle']['icon']) && fs::exists( $this->project->getRootDir() . "/vendor/" . $name . "/src/.data/img/" . $packageData['ide-bundle']['icon'])){
                    $icon = new UXImageView;
                    $icon->image = $this->project->getRootDir() . "/vendor/" . $name . "/src/.data/img/" . $packageData['ide-bundle']['icon'];
                } else {
                    $icon = ico('bundle16');
                }
            } else {
                $icon = ico('plugin16');
            }
            $icon->x =
            $icon->y = 8;
            $icon->width =
            $icon->height = 16;
            $iconPane = new UXAnchorPane;
            $iconPane->add($icon);
            $iconPane->padding = 4;

            $packageBox = new UXHBox([$iconPane, $textBox]);
            $packageBox->spacing = 5;
            $packageBox->data('name', $name);
            $packageBox->data('version', $version);

            $this->packagesList->items->add($packageBox);
            $this->packagesList->requestFocus();
        }
    }

    /**
     * Обновление репозитория
     */
    public function doUpdateRepos(){
        $this->repoUpdateButton->enabled =
        $this->paginationBox->children->toArray()[0]->enabled =
        $this->paginationBox->children->toArray()[1]->enabled = false;
        $this->repoUpdateButton->graphic = ico('process16');

        $packages = $this->packageTpl->getDeps();
        $thread = new Thread(function() use ($packages){
            /** @var ServiceResponse $query */
            $query = Ide::service()->ide()->executeGet('repo/list/last?limit=31&offset=' . $this->offset);
            if($query->isSuccess()){
                $data = $query->result();
            } else {
                $data = [];
            }

            uiLaterAndWait(function () use ($data) {
                $this->paginationBox->children->toArray()[0]->enabled = $this->offset != 0;
                $this->paginationBox->children->toArray()[1]->enabled = count($data) == 31;
            });

            if (count($data) == 31)
                array_pop($data);

            uiLater(function() use ($data, $packages){
                $this->repoList->items->clear();
                $lastName = null;
                foreach($data as $item){
                    if($item['name'] != $lastName){
                        $box = new UXVBox;
                        $box->data('name', $item['name']);
                        $box->data('version', '*');
                        $this->repoList->items->add($box);
                        
                        $labelName = new UXLabel($item['name']);
                        $labelName->font = $labelName->font->withBold()->withSize(14);
                        $labelName->textColor = UXColor::of('#333');
                        $box->add($labelName);
                        
                        $descText = (is_null($item['description']) ? 'No description' : $item['description']);
                        $descLabel = new UXLabel($descText);
                        $descLabel->font = $descLabel->font->withSize(12);
                        $descLabel->textColor = UXColor::of('#333');
                        $box->add($descLabel);                    
                        
                        $versionLabel = new UXLabel(_('jppm.package.manager.version') . ': *', ico('tag16'));
                        $versionLabel->graphicTextGap = 24;
                        $versionLabel->font = $versionLabel->font->withSize(12);
                        $versionLabel->textColor = UXColor::of('#666');
                        $box->add($versionLabel);

                        if(isset($packages[$item['name']]) && $packages[$item['name']] == '*'){
                            $versionLabel->graphic = ico('ok16');
                            $versionLabel->enabled = false;
                        }
                    }
                    
                    $label = new UXLabel(_('jppm.package.manager.version') . ': ' . $item['version'], ico('tag16'));
                    $label->data('name', $item['name']);
                    $label->data('version', $item['version']);
                    $label->graphicTextGap = $versionLabel->graphicTextGap;
                    $label->font = $versionLabel->font;
                    $label->textColor = $versionLabel->textColor;
                    $this->repoList->items->add($label);
                    
                    if(isset($packages[$item['name']]) && $packages[$item['name']] == $item['version']){
                        $label->graphic = ico('ok16');
                        $label->enabled = false;
                    }

                    $lastName = $item['name'];
                }
                
                $this->repoUpdateButton->enabled = true;
                $this->repoUpdateButton->graphic = ico('refresh16');
            });
        });
        $thread->start();
    }

    /**
     * Создать контекстное меню для списка с пакетами. Элементы в items должны хранить данные в data('name') и data('version')
     * @return UXContextMenu
     */
    protected function uiPackageContextMenu(UXListView $list){
        $list->contextMenu = new UXContextMenu;

        $copyLinkMenu = new UXMenuItem('Копировать ссылку', ico('copy16'));
        $copyLinkMenu->on('action', function() use ($list){
            if($list->selectedIndex < 0) return;
            $selected = $list->selectedItem;
            $link = $selected->data('name') . '@' . $selected->data('version');
            UXClipboard::setText($link);
            Ide::get()->getMainForm()->toast('Скопировано: ' . $link);
        });
        $list->contextMenu->items->add($copyLinkMenu);
    }

    /**
     * Выбор элемента из репозитория
     */
    public function doSelectRepo(){
        if($this->repoList->selectedIndex < 0) return;
        
        $selected = $this->repoList->selectedItem;
        $this->nameField->text = $selected->data('name');
        $this->versionField->text = $selected->data('version');
    }

    /**
     * Открыть readme для текущего пакета
     */
    public function doBrowseReadme(){
        $selected = $this->packagesList->selectedItem->data('name');
        $info = $this->jppm->getDepConfig($selected);
        $url = str_replace('%name%', $info['name'], $info['doc']['url-prefix']);
        browse($url);
    }

    /**
     * Выбор пакета из списка
     */
    public function doSelectPackage(){
        $this->delButton->enabled = $this->packagesList->selectedIndex >= 0;

        if($this->delButton->enabled){
            $selected = $this->packagesList->selectedItem->data('name');
            $info = $this->jppm->getDepConfig($selected);
            $this->readmeButton->enabled = isset($info['doc']['url-prefix']);
        }
    }

    /**
     * Добавление пакета
     */
    public function doAddPackage(){
        $package = $this->nameField->text;
        $version = $this->versionField->text;

        if(strlen($package) == 0){
            $error = new UXAlert('ERROR');
            $error->title = _('entity.error');
            $error->headerText = _('jppm.package.manager.error.name');
            $error->showAndWait();
            return;
        }

        if(strlen($version) == 0){
            $version = '*';
        }

        $packages = $this->packageTpl->getDeps();
        if(isset($packages[$package]) && $packages[$package] == $version){
            $error = new UXAlert('ERROR');
            $error->title = _('entity.error');
            $error->headerText = _('jppm.package.manager.error.exists');
            $error->showAndWait();
            return;
        }

        $this->jppm->addDep($package, $version);
        $this->applyPackages($package);

        $this->nameField->text = null;
        $this->versionField->text = null;
    }

    /**
     * Удаление пакета
     * @param string|null $package Если не передано имя пакета, то будет взят выбранный элемент из списка пакетов
     */
    public function doDeletePackage($package = null){
        if(is_null($package) || !is_string($package)){
            $package = $this->packagesList->selectedItem->data('name');
            $this->packagesList->items->removeByIndex($this->packagesList->selectedIndex);
        }

        $this->jppm->removeDep($package);
        $this->applyPackages();

        $this->delButton->enabled = false;
    }

    /**
     * После установки или удаления пакетов обновляет их в системе и проекте
     * @param  string|null $lastInstall Имя последнего добавленного пакета, если произойдет ошибка, он будет удалён
     */
    protected function applyPackages(?string $lastInstall = null){
        $preloader = $this->createPreloader();
        $this->packagesList->items->add($preloader);
        $this->packagesList->scrollTo($this->packagesList->items->count());
        $this->disableUI();

        $this->jppm->install($this->project, 

            function(){
                // on finish
                $this->refresh();
                $this->jppm->installToIDE($this->project);
                $this->project->refreshSupports();
            },

            function($msg) use ($lastInstall){
                // on error callback
                
                if(str::posIgnoreCase($msg, 'failed to install') > -1){
                    Logger::error('Cannot install package ' . $lastInstall);

                    if(str::length($lastInstall) > 0){
                        $this->doDeletePackage($lastInstall);
                    }
                
                    $error = new UXAlert('ERROR');
                    $error->title = _('entity.error');
                    $error->headerText = _('jppm.package.manager.error.install') . ' ' . $lastInstall;
                    $error->contentText = $msg;
                    $error->showAndWait();
                } elseif(str::posIgnoreCase($msg, 'failed to delete') > -1){
                    Ide::get()->getMainForm()->toast(_('jppm.package.manager.error.delete'));
                } else {
                    Ide::get()->getMainForm()->toast(_('jppm.package.manager.error.default'));
                }

                /*$this->refresh();
                $this->jppm->installToIDE($this->project);
                $this->project->refreshSupports();
                $this->doUpdateRepos();*/
            }
        );
    }

    /**
     * Выключить возможность взаимодействия с графическими элементами
     */
    protected function disableUI(){
        $this->nameField->enabled = 
        $this->versionField->enabled = 
        $this->repoList->enabled = 
        $this->repoUpdateButton->enabled = 
        $this->readmeButton->enabled = 
        $this->addButton->enabled = 
        $this->delButton->enabled = 
        !$this->parentPane->mouseTransparent = true;
        $this->packagesList->selectedIndex = -1;
        $this->paginationBox->children->toArray()[0]->enabled =
        $this->paginationBox->children->toArray()[1]->enabled = false;
    }

    /**
     * Включить возможность взаимодействия с графическими элементами
     */
    protected function enableUI(){
        $this->nameField->enabled = 
        $this->versionField->enabled = 
        $this->repoList->enabled = 
        $this->repoUpdateButton->enabled = 
        $this->addButton->enabled = 
        !$this->parentPane->mouseTransparent = false;
        $this->packagesList->selectedIndex = -1;
        $this->paginationBox->children->toArray()[0]->enabled =
        $this->paginationBox->children->toArray()[1]->enabled = true;
    }
}