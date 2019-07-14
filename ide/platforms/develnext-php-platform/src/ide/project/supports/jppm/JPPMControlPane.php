<?php
namespace ide\project\supports\jppm;

use ide\Ide;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\misc\FileWatcher;
use ide\project\behaviours\BundleProjectBehaviour;
use ide\project\control\AbstractProjectControlPane;
use ide\project\supports\JPPMProjectSupport;
use php\gui\UXButton;
use php\gui\UXLabel;
use php\gui\UXListView;
use php\gui\UXNode;
use php\gui\UXTextField;
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
    protected $delBtn;

    /**
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
        $parentPane = new UXVBox;
        
        $parentPane->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => 0];
        $parentPane->padding = 5;
        $parentPane->spacing = 3;      
        
        $this->packagesList = new UXListView;
        $this->packagesList->anchors = ['top' => 0, 'left' => 0, 'right' => 0, 'bottom' => 0];
        UXHBox::setHgrow($this->packagesList, 'ALWAYS');
        
        $this->delBtn = new UXButton('Delete package');
        $this->delBtn->enabled = false;
        $this->delBtn->width = 120;
        
        
        $this->packagesList->observer('focused')->addListener(function(){
            $this->delBtn->enabled = $this->packagesList->selectedIndex >= 0;
        });
        
        $this->packagesList->on('click', function(){
            $this->delBtn->enabled = $this->packagesList->selectedIndex >= 0;
        });
        
        
        $packBox = new UXHBox([$this->packagesList, $this->delBtn]);
        $packBox->spacing = 3;  
        $parentPane->add($packBox);
              
        
        $this->nameField = new UXTextField;
        $this->nameField->promptText = "Package name";
        UXHBox::setHgrow($this->nameField, 'ALWAYS');
        
        $this->versionField = new UXTextField();
        $this->versionField->promptText = "Version: *";
        $this->versionField->maxWidth = 100;
        $addBtn = new UXButton('Add package');
        $addBtn->width = 120;
        
        
        $addBox = new UXHBox([$this->nameField, $this->versionField, $addBtn]);
        $addBox->anchors = ['top' => false, 'left' => 0, 'right' => 0, 'bottom' => false];
        $addBox->spacing = 3;
        $parentPane->add($addBox);


        $delBtn->on('click', [$this, 'doDeletePackage']);
        $addBtn->on('click', [$this, 'doAddPackage']);
        return $parentPane;
    }

    /**
     * Refresh ui and pane.
     */
    public function refresh() {
        if(is_null($this->packageTpl)) return;

        Ide::get()->getMainForm()->showPreloader('...');
        $this->packagesList->items->clear();
        $this->packageTpl->load();
        $packages = $this->packageTpl->getDeps();

        foreach ($packages as $name => $version){
            $labelName = new UXLabel($name);
            $labelName->textColor = UXColor::of('#000000');
            $labelName->font = $labelName->font->withBold();

            $labelVersion = new UXLabel($version);
            $labelVersion->textColor = UXColor::of('#888888');
            $labelVersion->font = $labelVersion->font->withSize(11);

            $packageBox = new UXVBox([$labelName, $labelVersion]);
            $packageBox->data('name', $name);
            $packageBox->data('version', $version);
            $this->packagesList->items->add($packageBox);
        }

        Ide::get()->getMainForm()->hidePreloader();
    }

    /**
     * При нажатии кнопки - добавим пакет в список
     */
    public function doAddPackage(){
        $package = $this->nameField->text;
        $version = $this->versionField->text;

        if(strlen($package) == 0){
            return alert('Введите имя пакета!');
        }

        if(strlen($version) == 0){
            $version = '*';
        }

        $this->jppm->addDep($package, $version, $this->project, function($msg){
            // on error callback
            uiLater(function() use ($msg, $package){
                alert("Package install error:\n" . $msg);
                $this->doDeletePackage($package);
            });
        });

        $this->refresh();

        $this->nameField->text = null;
        $this->versionField->text = null;
    }

    public function doDeletePackage($package = null){
        $package = (is_null($package) || !is_string($package)) ? ($this->packagesList->selectedItem->data('name')) : ($package) ;

        $this->jppm->removeDep($package);
        $this->refresh();
        $this->delBtn->enabled = false;
    }
}