<?php
namespace ide\editors;

use Exception;
use ide\commands\NewProjectCommand;
use ide\commands\OpenProjectCommand;
use ide\Ide;
use ide\ui\elements\DNAnchorPane;
use ide\ui\elements\DNButton;
use ide\ui\elements\DNLabel;
use ide\ui\elements\DNSeparator;
use php\gui\layout\UXAnchorPane;
use php\gui\UXButton;
use php\gui\UXLabel;
use php\gui\UXLoader;
use php\gui\UXNode;
use php\gui\UXSeparator;

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
        $loader = new UXLoader();

        /** @var UXAnchorPane $layout */
        $layout = _($loader->load('res://.forms/blocks/_Welcome.fxml'));
        DNAnchorPane::applyIDETheme($layout);

        /** @var UXLabel $welcomeLabel */
        $welcomeLabel = $layout->lookup('#welcomeLabel');
        DNLabel::applyIDETheme($welcomeLabel);

        /** @var UXSeparator $welcomeSeparator */
        $welcomeSeparator = $layout->lookup('#welcomeSeparator');
        DNSeparator::applyIDETheme($welcomeSeparator);

        /** @var UXButton $createProjectButton */
        $createProjectButton = $layout->lookup('#createProjectButton');
        $createProjectButton->on('click', function () {
            Ide::get()->executeCommand(NewProjectCommand::class);
        });

        /** @var UXButton $openProjectButton */
        $openProjectButton = $layout->lookup('#openProjectButton');
        $openProjectButton->on('click', function () {
            Ide::get()->executeCommand(OpenProjectCommand::class);
        });

        DNButton::applyIDETheme($createProjectButton);
        DNButton::applyIDETheme($openProjectButton);

        return $layout;
    }
}
