<?php
namespace ide\forms;

use ide\Ide;
use ide\tool\AbstractToolInstaller;
use php\gui\framework\AbstractForm;
use php\gui\UXDialog;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXTextArea;

/**
 * Class ToolInstallForm
 * @package ide\forms
 *
 * @property UXLabel $titleLabel
 * @property UXImageView icon
 * @property UXTextArea $console
 */
class ToolInstallForm extends AbstractForm
{
    /**
     * @var AbstractToolInstaller
     */
    private $installer;

    /**
     * ToolInstallForm constructor.
     * @param AbstractToolInstaller $installer
     * @throws \Exception
     */
    public function __construct(AbstractToolInstaller $installer)
    {
        $this->installer = $installer;

        parent::__construct();

        $installer->on('message', function ($message, $type) {
            uiLater(function () use ($message, $type) {
                if ($type)
                    $this->console->text .= "[$type] $message\n";
                else $this->console->text .= $message;

                $this->console->end();
            });
        }, __CLASS__);

        $installer->on('done', function ($success) {
            uiLater(function () use ($success) {
                if (!$success) {
                    UXDialog::showAndWait(_("tool.install.error", $this->installer->getTool()->getName()), 'ERROR');
                }

                $this->hide();
                Ide::get()->getMainForm()->hidePreloader();
            });
        }, __CLASS__);

        $this->on("show", function () {
            $this->installer->run()->start();
        });
    }

    public function showAndWait() {
        Ide::get()->getMainForm()->showPreloader(_("tool.install.title", $this->installer->getTool()->getName()));

        parent::showAndWait();
    }

    protected function init()
    {
        parent::init();

        $this->icon->image = Ide::getImage($this->installer->getTool()->getIcon(), [32, 32])->image;
        $this->title = _($this->title, $this->installer->getTool()->getName());
        _($this->layout, $this->installer->getTool()->getName());
    }
}