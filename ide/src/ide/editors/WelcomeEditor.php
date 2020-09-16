<?php
namespace ide\editors;

use Exception;
use ide\commands\NewProjectCommand;
use ide\commands\OpenProjectCommand;
use ide\commands\SettingsShowCommand;
use ide\Ide;
use ide\ui\elements\DNAnchorPane;
use ide\ui\elements\DNButton;
use ide\ui\elements\DNLabel;
use ide\ui\elements\DNSeparator;
use php\gui\layout\UXVBox;
use php\gui\UXNode;

class WelcomeEditor extends AbstractEditor
{
    public function isCloseable() {
        return false;
    }

    public function getTitle() {
        return _('welcome.title');
    }

    public function isAutoClose() {
        return false;
    }

    public function load() {
        // nop.
    }

    public function save() {
        // nop.
    }

    /**
     * @return UXNode
     * @throws Exception
     */
    public function makeUi() {
        $layout = new UXVBox();
        $layout->padding = 24;
        $layout->spacing = 8;
        DNAnchorPane::applyIDETheme($layout);

        $welcomeLabel = new DNLabel(_("welcome.title"));
        $welcomeLabel->font = $welcomeLabel->font->withBold()->withSize(24);

        $layout->add($welcomeLabel);
        $layout->add($separator = new DNSeparator());
        $separator->height = 24;

        $createProjectButton = new DNButton(_("welcome.project.create"), ico("new16"));
        $createProjectButton->font = $createProjectButton->font->withSize(16);
        $createProjectButton->width = 250;
        $createProjectButton->alignment = 'BASELINE_LEFT';
        $createProjectButton->on('click', function () {
            Ide::get()->executeCommand(NewProjectCommand::class);
        });

        $layout->add($createProjectButton);

        $openProjectButton = new DNButton(_("welcome.project.open"), ico("open16"));
        $openProjectButton->font = $openProjectButton->font->withSize(16);
        $openProjectButton->width = 250;
        $openProjectButton->alignment = 'BASELINE_LEFT';
        $openProjectButton->on('click', function () {
            Ide::get()->executeCommand(OpenProjectCommand::class);
        });

        $layout->add($openProjectButton);

        $openSettingsButton = new DNButton(_("welcome.settings.open"), ico("settings16"));
        $openSettingsButton->font = $openProjectButton->font->withSize(16);
        $openSettingsButton->width = 250;
        $openSettingsButton->alignment = 'BASELINE_LEFT';
        $openSettingsButton->on('click', function () {
            Ide::get()->executeCommand(SettingsShowCommand::class);
        });

        $layout->add($openSettingsButton);
        return $layout;
    }
}
