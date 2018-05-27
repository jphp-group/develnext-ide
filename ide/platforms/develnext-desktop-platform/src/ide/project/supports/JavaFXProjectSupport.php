<?php
namespace ide\project\supports;

use ide\action\ActionManager;
use ide\behaviour\IdeBehaviourDatabase;
use ide\formats\GuiFormFormat;
use ide\Ide;
use ide\Logger;
use ide\project\AbstractProjectSupport;
use ide\project\Project;

/**
 * Class JavaFXProjectSupport
 * @package ide\project\supports
 */
class JavaFXProjectSupport extends AbstractProjectSupport
{
    /**
     * @param Project $project
     * @return mixed
     * @throws \Exception
     */
    public function isFit(Project $project)
    {
        /** @var JPPMProjectSupport $jppm */
        if ($jppm = $project->findSupport('jppm')) {
            return $jppm->hasDep('jphp-gui-ext') || $jppm->hasDep('dn-app-framework');
        } else {
            return false;
        }
    }

    /**
     * @param Project $project
     * @return mixed
     * @throws \ide\IdeException
     */
    public function onLink(Project $project)
    {
        $format = Ide::get()->getRegisteredFormat(GuiFormFormat::class);

        if ($format) {
            $format->registerInternalList('.dn/bundle/uiDesktop/formComponents');
        } else {
            Logger::error("Unable to register components, GuiFormFormat is not found.");
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->registerInternalList('.dn/bundle/uiDesktop/behaviours');
        }

        if ($aManager = ActionManager::get()) {
            $aManager->registerInternalList('.dn/bundle/uiDesktop/actionTypes');
        }
    }

    /**
     * @param Project $project
     * @return mixed
     * @throws \ide\IdeException
     */
    public function onUnlink(Project $project)
    {
        $format = Ide::get()->getRegisteredFormat(GuiFormFormat::class);

        if ($format) {
            $format->unregisterInternalList('.dn/bundle/uiDesktop/formComponents');
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->unregisterInternalList('.dn/bundle/uiDesktop/behaviours');
        }

        if ($aManager = ActionManager::get()) {
            $aManager->unregisterInternalList('.dn/bundle/uiDesktop/actionTypes');
        }
    }

    public function getCode()
    {
        return 'javafx';
    }
}