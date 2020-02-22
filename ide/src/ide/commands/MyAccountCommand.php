<?php

namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\editors\menu\ContextMenu;
use ide\Ide;
use ide\misc\AbstractCommand;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXPanel;
use php\gui\UXButton;
use php\gui\UXImageArea;
use php\gui\UXSeparator;

class MyAccountCommand extends AbstractCommand
{
    /**
     * @var UXButton
     */
    protected $accountButton;

    /**
     * @var UXImageArea
     */
    protected $accountImage;

    /**
     * @var UXPanel
     */
    protected $accountImagePanel;

    /**
     * @var ContextMenu
     */
    protected $contextMenu;

    /**
     * MyAccountCommand constructor.
     */
    public function __construct()
    {
        Ide::service()->on('privateEnable', function () {
            $this->accountButton->enabled = true;
        }, __CLASS__);

        Ide::service()->on('privateDisable', function () {
            $this->accountButton->enabled = false;
            $this->accountImage->image = Ide::get()->getImage('noAvatar.jpg')->image;
        }, __CLASS__);

        Ide::accountManager()->on('update', function ($data) {
            $this->accountButton->text = $data ? $data['login'] : 'account.log.in';
            $this->accountButton = _($this->accountButton);

            Ide::service()->file()->loadImage($data['avatarId'], $this->accountImage, 'noAvatar.jpg');
        }, __CLASS__);

        $this->contextMenu = new ContextMenu();
        $this->contextMenu->setCssClass('account-menu');
    }

    public function getName()
    {
        return 'account.my';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        if (Ide::accountManager()->isAuthorized()) {
            $this->contextMenu->clear();

            foreach (Ide::get()->getInternalList('.dn/account/menuCommands') as $class) {
                $this->contextMenu->addCommand(new $class());
            }

            $this->contextMenu->getRoot()->showByNode($this->accountButton, 0, 33);
        } else {
            Ide::accountManager()->authorize(true);
        }
    }

    /*public function getIcon()
    {
        return 'icons/account16.png';
    }*/

    public function makeMenuItem()
    {
        return null;
    }

    public function makeUiForHead()
    {
        $btn = $this->makeGlyphButton();
        $btn->text = $this->getName();
        $btn->style .= "-fx-font-weight: bold;";
        $btn->classes->addAll(['flat-button']);
        $btn->paddingLeft = $btn->paddingRight = 15;

        $this->accountButton = $btn;

        $this->accountImage = new UXImageArea();
        $this->accountImage->centered = true;
        $this->accountImage->proportional = true;
        $this->accountImage->stretch = true;
        $this->accountImage->smartStretch = true;
        $this->accountImage->position = [0, 0];

        $panel = new UXPanel();
        $panel->add($this->accountImage);
        $panel->borderWidth = 1;
        $panel->borderColor = 'silver';
        $panel->maxHeight = 999;
        $panel->width = 30;

        $this->accountImagePanel = $panel;
        UXAnchorPane::setAnchor($this->accountImage, 0);

        return [$panel, $btn, new UXSeparator('VERTICAL')];
    }

    public function isAlways()
    {
        return true;
    }
}