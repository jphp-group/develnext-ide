<?php
namespace ide\editors;

use develnext\lexer\inspector\PHPInspector;
use ide\autocomplete\php\PhpAutoComplete;
use ide\commands\NewProjectCommand;
use ide\commands\OpenProjectCommand;
use ide\editors\rich\autocomplete\AutoCompletePane;
use ide\editors\rich\highlighters\CssANTLR4Highlighter;
use ide\editors\rich\highlighters\JsonANTLR4Highlighter;
use ide\editors\rich\highlighters\PhpANTLR4Highlighter;
use ide\editors\rich\LineNumber;
use ide\editors\rich\RichCodeEditor;
use ide\Ide;
use php\gui\UXLoader;
use php\gui\UXNode;

class WelcomeEditor extends AbstractEditor
{
    public function isCloseable()
    {
        return false;
    }

    public function getTitle()
    {
        return _('welcome.title');
    }

    public function isAutoClose()
    {
        return false;
    }

    public function load()
    {
        // nop.
    }

    public function save()
    {
        // nop.
    }

    /**
     * @return UXNode
     */
    public function makeUi()
    {
        $loader = new UXLoader();

        $layout = _($loader->load('res://.forms/blocks/_Welcome.fxml'));

        $layout->lookup('#createProjectButton')->on('click', function () {
            Ide::get()->executeCommand(NewProjectCommand::class);
        });

        $layout->lookup('#openProjectButton')->on('click', function () {
            Ide::get()->executeCommand(OpenProjectCommand::class);
        });

        return $layout;
    }
}