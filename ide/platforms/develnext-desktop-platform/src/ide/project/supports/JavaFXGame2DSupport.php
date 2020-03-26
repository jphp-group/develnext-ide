<?php
namespace ide\project\supports;

use ide\action\ActionManager;
use ide\behaviour\IdeBehaviourDatabase;
use ide\commands\CreateGameSpriteProjectCommand;
use ide\editors\GameSpriteEditor;
use ide\formats\GuiFormFormat;
use ide\formats\ProjectFormat;
use ide\formats\sprite\IdeSpriteManager;
use ide\Ide;
use ide\Logger;
use ide\project\AbstractProjectSupport;
use ide\project\control\SpritesProjectControlPane;
use ide\project\Project;
use ide\systems\FileSystem;
use ide\utils\FileUtils;

class JavaFXGame2DSupport extends AbstractProjectSupport
{
    public function getCode()
    {
        return 'javafx-game';
    }

    public function getFitRequiredSupports(): array
    {
        return ['javafx', 'jppm'];
    }

    /**
     * @inheritDoc
     */
    public function isFit(Project $project)
    {
        if ($project->hasSupport('javafx') && $project->hasSupport('jppm')) {
            $jppm = $project->findSupport('jppm');
            return $jppm->hasDep('jphp-gui-game-ext');
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function onLink(Project $project)
    {
        $project->on('update', fn() => $this->onProjectUpdate($project), self::class);

        $project->data(self::class . '#spriteManager', new IdeSpriteManager($project));

        $tree = $project->getTree();
        $menu = $tree->getContextMenu();

        $projectTreeNewMenuItems = [];
        $createGameSpriteProjectCommand = new CreateGameSpriteProjectCommand();
        $projectTreeNewMenuItems[] = $menu->add($createGameSpriteProjectCommand, 'new');
        $project->data(self::class . '#treeNewMenuItems', $projectTreeNewMenuItems);


        /** @var ProjectFormat $projectFormat */
        $projectFormat = Ide::get()->getRegisteredFormat(ProjectFormat::class);

        if ($projectFormat) {
            $projectFormat->addControlPane(new SpritesProjectControlPane());
        }

        $format = Ide::get()->getRegisteredFormat(GuiFormFormat::class);

        if ($format) {
            $format->registerInternalList('.dn/bundle/game2d/formComponents');
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->registerInternalList('.dn/bundle/game2d/behaviours');
        }

        $project->whenSupportLinked(
            'visprog',
            fn(AbstractProjectSupport $visprog) => $visprog->registerActions($project, '.dn/bundle/game2d/actionTypes'), self::class
        );

        $menuForAddTab = FileSystem::getMenuForAddTab();
        if ($menuForAddTab) {
            $added = [];
            $added[] = $menuForAddTab->add($createGameSpriteProjectCommand);
            $project->data(self::class . '#treeAddMenu', $added);
        }

        $this->updateSpriteManager($project);
    }

    /**
     * @inheritDoc
     */
    public function onUnlink(Project $project)
    {
        $project->offGroup(self::class);

        $project->whenSupportUnlinked(
            'visprog',
            fn(AbstractProjectSupport $visprog) => $visprog->unregisterActions($project, '.dn/bundle/game2d/actionTypes'), self::class
        );

        /** @var ProjectFormat $projectFormat */
        $projectFormat = Ide::get()->getRegisteredFormat(ProjectFormat::class);
        if ($projectFormat) {
            $projectFormat->removeControlPane(SpritesProjectControlPane::class);
        }

        $format = Ide::get()->getRegisteredFormat(GuiFormFormat::class);

        if ($format) {
            $format->unregisterInternalList('.dn/bundle/game2d/formComponents');
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->unregisterInternalList('.dn/bundle/game2d/behaviours');
        }

        if ($aManager = ActionManager::get()) {
            $aManager->unregisterInternalList('.dn/bundle/game2d/actionTypes');
        }

        $this->getSpriteManager($project)->free();

        $tree = $project->getTree();
        $menu = $tree->getContextMenu();
        $menu->remove((array) $project->data(self::class . '#treeNewMenuItems'), 'new');

        $menuForAddTab = FileSystem::getMenuForAddTab();
        if ($menuForAddTab) {
            $menuForAddTab->remove($project->data(self::class . '#treeAddMenu'));
        }

        $project->data(self::class . '#treeNewMenuItems', null);
        $project->data(self::class . '#spriteManager', null);
        $project->data(self::class . '#treeAddMenu', null);

    }

    protected function onProjectUpdate(Project $project)
    {
        $this->getSpriteManager($project)->reloadAll();
    }

    public function getSpriteManager(Project $project): ?IdeSpriteManager
    {
        return $project->data(self::class . "#spriteManager");
    }

    public function createSprite(Project $project, $name)
    {
        Logger::info("Creating game sprite '$name' ...");

        $file = $this->getSpriteManager($project)->createSprite($name);

        Logger::info("Finish creating game sprite '$name'");

        return $file;
    }

    /**
     * @param Project $project
     * @return GameSpriteEditor[]
     */
    public function getSpriteEditors(Project $project): array
    {
        $editors = [];

        foreach ($this->getSpriteManager($project)->getSprites() as $spec) {
            $file = $spec->schemaFile;
            $editor = FileSystem::fetchEditor($file, true);

            if ($editor) {
                $editors[FileUtils::hashName($spec->file)] = $editor;
            } else {
                Logger::error("Unable to find sprite editor for $file");
            }
        }

        return $editors;
    }

    public function updateSpriteManager(Project $project)
    {
        $this->getSpriteManager($project)->reloadAll();
    }
}