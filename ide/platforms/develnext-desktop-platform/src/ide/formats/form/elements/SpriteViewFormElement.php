<?php
namespace ide\formats\form\elements;

use game\SpriteManager;
use ide\behaviour\spec\GameEntityBehaviourSpec;
use ide\formats\form\AbstractFormElement;
use ide\Ide;
use ide\project\Project;
use ide\project\supports\JavaFXGame2DSupport;
use php\game\UXSprite;
use php\game\UXSpriteView;
use php\gui\designer\UXDesigner;
use php\gui\framework\DataUtils;
use php\gui\UXNode;

class SpriteViewFormElement extends AbstractFormElement
{
    public function getGroup()
    {
        return '2D Игра';
    }

    public function getElementClass()
    {
        return UXSpriteView::class;
    }

    public function getName()
    {
        return 'Игровой объект';
    }

    public function getIcon()
    {
        return 'icons/spriteObject16.png';
    }

    public function getIdPattern()
    {
        return "object%s";
    }

    /**
     * @return UXNode
     */
    public function createElement()
    {
        $sprite = new UXSprite();
        $sprite->image = ico('grayQuestion16')->image;

        $object = new UXSpriteView($sprite);

        return $object;
    }

    public function getInitialBehaviours()
    {
        return [
            new GameEntityBehaviourSpec(false)
        ];
    }

    public function getIndexData(UXNode $node)
    {
        $data = DataUtils::get($node);

        return [
            'sprite' => $data->get('sprite'),
        ];
    }

    public function getCustomPreviewImage(array $indexData)
    {
        /** @var JavaFXGame2DSupport $gui */
        $gui = Project::findSupportOfCurrent('javafx-game');

        if ($gui) {
            return $gui->getSpriteManager(Ide::project())->getSpritePreview($indexData['sprite']);
        }

        return null;
    }

    public function refreshNode(UXNode $node, UXDesigner $designer)
    {
        parent::refreshNode($node, $designer);

        $project = Ide::project();

        /** @var UXSpriteView $node */
        $node->animated = false;

        if ($project && $project->hasSupport('javafx-game')) {
            /** @var JavaFXGame2DSupport $behaviour */
            $behaviour = $project->findSupport('javafx-game');

            $manager = $behaviour->getSpriteManager($project);

            if (!$node->parent) {
                return;
            }

            $data = DataUtils::get($node);
            $spec = $manager->get($data->get('sprite'));

            if ($spec) {
                $sprite = new UXSprite();
                $sprite->frameSize = [$spec->frameWidth, $spec->frameHeight];
                $sprite->speed = $spec->speed;
                $spritePreviewImage = $manager->getSpritePreview($data->get('sprite'));

                if (!$spritePreviewImage) {
                    waitAsync(200, function () use ($node, $designer) {
                        $this->refreshNode($node, $designer);
                    });
                    return;
                }

                $sprite->image = $spritePreviewImage;

                /*if ($spec->file) {
                    $file = $project->getFile("src/{$spec->file}");

                    if ($file->isFile()) {
                        $sprite->image = Cache::getImage($file);
                    }
                } */

                foreach ($spec->animations as $name => $indexes) {
                    $sprite->setAnimation($name, $indexes);
                }

               // $sprite->currentAnimation = $spec->defaultAnimation;
            } else {
                $sprite = new UXSprite();
                $sprite->image = ico('grayQuestion16')->image;
            }

            $node->sprite = $sprite;
        }
    }

    public function getDefaultSize()
    {
        return [32, 32];
    }

    public function isOrigin($any)
    {
        return $any instanceof UXSpriteView;
    }
}
