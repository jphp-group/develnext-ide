<?php
namespace ide\project\control;

use ide\commands\CreateGameSpriteProjectCommand;
use ide\editors\GameSpriteEditor;
use ide\Ide;
use ide\project\Project;
use ide\project\supports\JavaFXGame2DSupport;


/**
 * @package ide\project\control
 */
class SpritesProjectControlPane extends AbstractEditorsProjectControlPane
{
    public function getName()
    {
        return "ui.game.sprites::Спрайты";
    }

    public function getDescription()
    {
        return "ui.game.graphics::Игровая графика";
    }

    public function getIcon()
    {
        return 'icons/album16.png';
    }

    /**
     * @return mixed
     */
    protected function doAdd()
    {
        $command = new CreateGameSpriteProjectCommand();
        $command->onExecute();
    }

    /**
     * @return mixed[]
     * @throws \Exception
     */
    protected function getItems()
    {
        $gui = Project::findSupportOfCurrent('javafx-game');

        return $gui ? $gui->getSpriteEditors(Ide::project()) : [];
    }

    /**
     * @param GameSpriteEditor $item
     * @return mixed
     * @throws \Exception
     */
    protected function getBigIcon($item)
    {
        $spec = $item->getSpec();
        /** @var JavaFXGame2DSupport $gui */
        $gui = Project::findSupportOfCurrent('javafx-game');

        if ($gui) {
            $image = $gui->getSpriteManager(Ide::project())->getSpritePreview($spec->name);

            if (!$image) {
                return ico('grayQuestion16')->image;
            }

            return $image;
        } else {
            return ico('grayQuestion16')->image;
        }
    }
}