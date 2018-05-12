<?php
namespace develnext\bundle\game2d;

use ide\action\ActionManager;
use ide\behaviour\IdeBehaviourDatabase;
use ide\bundle\AbstractBundle;
use ide\bundle\AbstractJarBundle;
use ide\formats\GuiFormFormat;
use ide\formats\ProjectFormat;
use ide\Ide;
use ide\library\IdeLibraryBundleResource;
use ide\project\behaviours\GuiFrameworkProjectBehaviour;
use ide\project\control\SpritesProjectControlPane;
use ide\project\Project;

class Game2DBundle extends AbstractJarBundle
{
    function getName()
    {
        return "2D Game";
    }

    function getDescription()
    {
        return "Пакет для создания простых 2D игр.";
    }

    public function isAvailable(Project $project)
    {
        return $project->hasBehaviour(GuiFrameworkProjectBehaviour::class);
    }

    public function onAdd(Project $project, AbstractBundle $owner = null)
    {
        parent::onAdd($project, $owner);

        /** @var ProjectFormat $projectFormat */
        $projectFormat = Ide::get()->getRegisteredFormat(ProjectFormat::class);
        $projectFormat->addControlPane(new SpritesProjectControlPane());

        $format = Ide::get()->getRegisteredFormat(GuiFormFormat::class);

        if ($format) {
            $format->registerInternalList('.dn/bundle/game2d/formComponents');
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->registerInternalList('.dn/bundle/game2d/behaviours');
        }

        if ($aManager = ActionManager::get()) {
            $aManager->registerInternalList('.dn/bundle/game2d/actionTypes');
        }
    }

    public function onRemove(Project $project, AbstractBundle $owner = null)
    {
        parent::onRemove($project, $owner);

        /** @var ProjectFormat $projectFormat */
        $projectFormat = Ide::get()->getRegisteredFormat(ProjectFormat::class);
        $projectFormat->removeControlPane(SpritesProjectControlPane::class);

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
    }

    public function onRegister(IdeLibraryBundleResource $resource)
    {
        parent::onRegister($resource);
    }
}