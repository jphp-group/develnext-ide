<?php
namespace ide\project\supports\jppm;

use ide\formats\ProjectFormat;
use ide\project\AbstractProjectSupport;
use ide\project\Project;
use ide\project\supports\jppm\tasks\JPPMBuildTaskConfiguration;
use ide\project\supports\jppm\tasks\JPPMStartTaskConfiguration;
use ide\project\supports\JPPMProjectSupport;
use php\lib\arr;

class JPPMAppPluginSupport extends AbstractProjectSupport
{
    public function getCode()
    {
        return 'jppm-app-plugin';
    }

    /**
     * @param Project $project
     * @return mixed
     * @throws \Exception
     */
    public function isFit(Project $project)
    {
        /** @var JPPMProjectSupport $jppm */
        if ($project->hasSupport('jppm')) {
            $jppm = $project->findSupport('jppm');
            return arr::has($jppm->getPkgTemplate()->getPlugins(), 'App');
        } else {
            return false;
        }
    }

    /**
     * @param Project $project
     */
    public function onLink(Project $project)
    {
        /** @var ProjectFormat $projectFormat */
        if ($projectFormat = $project->getRegisteredFormat(ProjectFormat::class)) {
            $projectFormat->addControlPane(new JPPMControlPane());
        }

        $project->getRunDebugManager()->add('jppm-start', new JPPMStartTaskConfiguration());
        $project->getRunDebugManager()->add('jppm-build', new JPPMBuildTaskConfiguration());
    }

    /**
     * @param Project $project
     */
    public function onUnlink(Project $project)
    {
        /** @var ProjectFormat $projectFormat */
        if ($projectFormat = $project->getRegisteredFormat(ProjectFormat::class)) {
            $projectFormat->removeControlPane(JPPMControlPane::class);
        }

        $project->getRunDebugManager()->remove('jppm-start');
        $project->getRunDebugManager()->remove('jppm-build');
    }
}
