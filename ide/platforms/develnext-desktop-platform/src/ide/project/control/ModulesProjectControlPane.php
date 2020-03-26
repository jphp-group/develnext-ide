<?php
namespace ide\project\control;

use ide\commands\CreateScriptModuleProjectCommand;
use ide\editors\ScriptModuleEditor;
use ide\Ide;
use php\gui\UXNode;
use ide\editors\AbstractEditor;

/**
 * @package ide\project\control
 */
class ModulesProjectControlPane extends AbstractEditorsProjectControlPane
{
    public function getName()
    {
        return "ui.modules::Модули";
    }

    public function getDescription()
    {
        return "ui.modules.and.scripts::Модули и скрипты";
    }

    public function getIcon()
    {
        return 'icons/blocks16.png';
    }

    /**
     * @return AbstractEditor[]
     * @throws \Exception
     */
    protected function getItems()
    {
        $project = Ide::project();
        $javafx = $project->findSupport('javafx');

        return $javafx ? $javafx->getModuleEditors($project) : [];
    }

    /**
     * @param ScriptModuleEditor $item
     * @return UXNode
     */
    protected function makeItemUi($item)
    {
        $box = parent::makeItemUi($item);

        if ($item->isAppModule()) {
            $box->setTitle($box->getTitle(), '-fx-font-weight: bold;');
        }

        return $box;
    }


    /**
     * @return mixed
     */
    protected function getBigIcon($item)
    {
        return 'icons/blocks32.png';
    }

    /**
     * @return mixed
     */
    protected function doAdd()
    {
        $command = new CreateScriptModuleProjectCommand();
        $command->onExecute();
    }
}