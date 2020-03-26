<?php
namespace ide\commands;

use develnext\bundle\game2d\Game2DBundle;
use Dialog;
use ide\editors\AbstractEditor;
use ide\editors\menu\AbstractMenuCommand;
use ide\formats\GameSpriteFormat;
use ide\Ide;
use ide\project\behaviours\BundleProjectBehaviour;
use ide\project\Project;
use ide\project\supports\JavaFXGame2DSupport;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\lib\Str;

class CreateGameSpriteProjectCommand extends AbstractMenuCommand
{
    public function getName()
    {
        return 'Новый спрайт';
    }

    public function getIcon()
    {
        return 'picture16';
    }

    public function getCategory()
    {
        return 'create';
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $ide = Ide::get();
        $project = $ide->getOpenedProject();

        if ($project) {
            $name = $ide->getRegisteredFormat(GameSpriteFormat::class)->showCreateDialog();

            if ($name !== null) {
                $name = str::trim($name);

                if (!FileUtils::validate($name)) {
                    return null;
                }

                /** @var JavaFXGame2DSupport $game2d */
                $game2d = $project->findSupport('javafx-game');

                if ($game2d) {
                    if ($game2d->getSpriteManager($project)->get($name)) {
                        Dialog::error('Спрайт с таким названием уже существует в проекте');
                        $this->onExecute();
                        return null;
                    }

                    $file = $game2d->createSprite($project, $name);
                    FileSystem::open($file);

                    return $name;
                }
            }
        }

        return null;
    }
}