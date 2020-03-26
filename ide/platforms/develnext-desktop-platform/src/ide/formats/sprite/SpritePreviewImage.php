<?php
namespace ide\formats\sprite;

use ide\Ide;
use ide\project\Project;
use ide\ui\LazyLoadingImage;

class SpritePreviewImage implements LazyLoadingImage
{
    /**
     * @var string
     */
    private $name;

    /**
     * SpritePreviewImage constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    function getImage()
    {
        $gui = Project::findSupportOfCurrent('javafx-game');

        if ($gui) {
            /** @var IdeSpriteManager $manager */
            if ($manager = $gui->getSpriteManager(Ide::project())) {
                return $manager->getSpritePreview($this->name);
            }
        }
    }
}