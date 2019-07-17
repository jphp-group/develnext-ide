<?php
namespace ide\project\supports\jppm;

use ide\Ide;
use ide\Logger;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\misc\FileWatcher;
use ide\project\behaviours\BundleProjectBehaviour;
use ide\project\control\AbstractProjectControlPane;
use ide\project\supports\JPPMProjectSupport;
use php\gui\UXAlert;
use php\gui\UXButton;
use php\gui\UXImage;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXListView;
use php\gui\UXNode;
use php\gui\UXProgressBar;
use php\gui\UXTextField;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\layout\UXPanel;
use php\gui\layout\UXVBox;
use php\gui\paint\UXColor;


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
     * @var UXVBox
     */
    protected $parentPane;

    /**
     * @todo Сделать поддержку языков
     * @return UXNode
     */
    protected function makeUi()
    {   
        $this->project = Ide::project();

        if($this->project->hasSupport('jppm')){
            $this->jppm = $this->project->findSupport('jppm');
            $this->packageTpl = $this->jppm->getPkgTemplate();
        }

        // GUI
        $this->parentPane = new UXVBox;
        
        $this->parentPane->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => 0];
        $this->parentPane->padding = 3;
        $this->parentPane->spacing = 5;      
        
        $this->packagesList = new UXListView;
        $this->packagesList->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => 0];
        UXHBox::setHgrow($this->packagesList, 'ALWAYS');
        
        $this->delButton = new UXButton(_('jppm.package.manager.delete'), ico('pluginRemove16'));
        $this->delButton->enabled = false;
        $this->delButton->width = 150;

        $this->readmeButton = new UXButton(_('jppm.package.manager.readme'), ico('search16'));
        $this->readmeButton->enabled = false;
        $this->readmeButton->width = 150;

        $buttonsBox = new UXVBox([$this->delButton, $this->readmeButton]);
        $buttonsBox->spacing = 5;  
        
        $packBox = new UXHBox([$this->packagesList, $buttonsBox]);
        $packBox->spacing = 5;  
        $this->parentPane->add($packBox);
                
        $this->nameField = new UXTextField;
        $this->nameField->promptText = _('jppm.package.manager.name');
        UXHBox::setHgrow($this->nameField, 'ALWAYS');
        
        $this->versionField = new UXTextField();
        $this->versionField->promptText = _('jppm.package.manager.version');
        $this->versionField->maxWidth = 165;
        $this->addButton = new UXButton(_('jppm.package.manager.add'), ico('pluginAdd16'));
        $this->addButton->width = 150;
        
        $addBox = new UXHBox([$this->nameField, $this->versionField, $this->addButton]);
        $addBox->anchors = ['top' => false, 'left' => 0, 'right' => 0, 'bottom' => false];
        $addBox->spacing = 5;
        $this->parentPane->add($addBox);  

        $this->delButton->on('click', [$this, 'doDeletePackage']);
        $this->readmeButton->on('click', [$this, 'doBrowseReadme']);
        $this->addButton->on('click', [$this, 'doAddPackage']);
        //$this->packagesList->observer('focused')->addListener([$this, 'doSelectPackage']);
        //$this->packagesList->on('click', [$this, 'doSelectPackage']);
        $this->packagesList->on('action', [$this, 'doSelectPackage']);

        return $this->parentPane;
    }

    protected function createPreloader(): UXNode {
        $progressBar = new UXProgressBar;
        $progressBar->progress = -1;
        $progressBar->height = 24;
        $progressBar->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => false];

        $progressPane = new UXAnchorPane;
        $progressPane->add($progressBar);
        $progressPane->paddingTop = 10;
        UXHBox::setHgrow($progressBar, 'ALWAYS');
        
        return $progressPane;
    }

    /**
     * Refresh ui and pane.
     */
    public function refresh() {
        if(is_null($this->packageTpl)) return;

        $this->disableUI();

        $this->packagesList->items->clear();
        $this->packageTpl->load();
        $packages = $this->packageTpl->getDeps();

        foreach ($packages as $name => $version){
            $packageData = $this->jppm->getDepConfig($name);
            $isBundle = isset($packageData['ide-bundle']);

            $nameLabel = new UXLabel($name);
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
            $icon = ico($isBundle ? 'bundle16' : 'plugin16');
            $icon->x =
            $icon->y = 8;
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

        $this->enableUI();
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
            var_dump($info);
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
            $error->title = _('jppm.package.manager.error.name');
            $error->showAndWait();
            return;
        }

        if(strlen($version) == 0){
            $version = '*';
        }

        $this->jppm->addDep($package, $version);
        $this->applyPackages($package);

        $this->nameField->text = null;
        $this->versionField->text = null;
    }

    /**
     * Удаление пакета
     */
    public function doDeletePackage($package = null){
        $package = (is_null($package) || !is_string($package)) ? ($this->packagesList->selectedItem->data('name')) : ($package) ;

        $this->jppm->removeDep($package);
        $this->applyPackages();

        $this->refresh();
        $this->delButton->enabled = false;
    }

    /**
     * После установки или удаления пакетов обновляет их в системе и проекте
     * @param  string|null $lastInstall Имя последнего добавленного пакета, если произойдет ошибка, он будет удалён
     */
    protected function applyPackages(?string $lastInstall = null){
        $preloader = $this->createPreloader();
        $this->packagesList->items->add($preloader);
        $this->disableUI();

        $this->jppm->install($this->project, 

            function(){
                // on finish
                $this->refresh();
            },

            function($msg) use ($lastInstall){
                // on error callback
                Logger::error('Cannot install package ' . $lastInstall);

                if(strlen($lastInstall) > 0){
                    $this->doDeletePackage($lastInstall);
                }

                $error = new UXAlert('ERROR');
                $error->title = _('entity.error');
                $error->headerText = _('jppm.package.manager.error.install') . ' ' . $lastInstall;
                $error->contentText = $msg;
                $error->showAndWait();

                $this->refresh();
            }
        );

        $this->jppm->installToIDE($this->project);
        $this->project->refreshSupports();
    }

    /**
     * Выключить возможность взаимодействия с графическими элементами
     */
    protected function disableUI(){
        $this->readmeButton->enabled = 
        $this->addButton->enabled = 
        $this->delButton->enabled = 
        !$this->parentPane->mouseTransparent = true;
        $this->packagesList->selectedIndex = -1;
    }

    /**
     * Включить возможность взаимодействия с графическими элементами
     */
    protected function enableUI(){
        $this->addButton->enabled = 
        !$this->parentPane->mouseTransparent = false;
        $this->packagesList->selectedIndex = -1;
    }
}