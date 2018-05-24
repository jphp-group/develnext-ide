<?php
namespace ide\bundle\std;

use ide\action\ActionManager;
use ide\behaviour\IdeBehaviourDatabase;
use ide\bundle\AbstractBundle;
use ide\bundle\AbstractJarBundle;
use ide\formats\GuiFormFormat;
use ide\Ide;
use ide\Logger;
use ide\project\Project;

/**
 * @package ide\bundle\std
 */
class UIDesktopBundle extends AbstractJarBundle
{
    function getName()
    {
        return "UI Desktop";
    }

    public function getDependencies()
    {
        return [
            JPHPCoreBundle::class,
        ];
    }

    /**
     * @return array
     */
    function getJarDependencies()
    {
        return [
            'jphp-gui-ext',
            'jphp-gui-tabs-ext',
            'jphp-graphic-ext',
            'jphp-gui-desktop-ext',
            'jphp-zend-ext',
            'dn-app-framework',
            'jphp-xml-ext',

            'gson', 'jphp-json-ext',
            'snakeyaml', 'jphp-yaml-ext',

            'wizard-core',
        ];
    }

    public function getJPPMDependencies()
    {
        return [
            'jphp-gui-ext' => '*',
            'jphp-gui-tabs-ext' => '*',
            'jphp-gui-desktop-ext' => '*',
            'jphp-zend-ext' => '*',
            'dn-app-framework' => '*',
            'jphp-yaml-ext' => '*',
            'wizard-core' => '*',
        ];
    }

    public function onAdd(Project $project, AbstractBundle $owner = null)
    {
        parent::onAdd($project, $owner);

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

    public function onRemove(Project $project, AbstractBundle $owner = null)
    {
        parent::onRemove($project, $owner);

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
}