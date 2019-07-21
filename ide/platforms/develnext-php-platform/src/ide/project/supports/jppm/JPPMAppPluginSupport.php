<?php
namespace ide\project\supports\jppm;

use framework\core\Event;
use ide\formats\ProjectFormat;
use ide\Ide;
use ide\project\AbstractProjectSupport;
use ide\project\Project;
use ide\project\supports\JPPMProjectSupport;
use ide\systems\IdeSystem;
use ide\systems\ProjectSystem;
use php\concurrent\Promise;
use php\lang\Process;
use php\lib\arr;
use Throwable;

class JPPMAppPluginSupport extends AbstractProjectSupport
{
    public function getCode()
    {
        return 'jppm-app-plugin';
    }

    /**
     * @param Project $project
     * @return mixed
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
     * @return mixed
     */
    public function onLink(Project $project)
    {
        /** @var ProjectFormat $projectFormat */
        if ($projectFormat = $project->getRegisteredFormat(ProjectFormat::class)) {
            $projectFormat->addControlPane(new JPPMControlPane());
        }

        $prepareFunc = function ($output): Promise {
            return new Promise(function ($resolve, $reject) use ($output) {
                try {
                    ProjectSystem::compileAll(Project::ENV_DEV, $output, "Prepare project ...", function () use ($resolve) {
                        $resolve(true);
                    });
                } catch (Throwable $e) {
                    $reject($e);
                }
            });
        };

        $project->getRunDebugManager()->add('jppm-start', [
            'title' => 'jppm.tasks.start.title',
            'prepareFunc' => $prepareFunc,
            'makeStartProcess' => function () use ($project) {
                $env = Ide::get()->makeEnvironment();

                $args = ['jppm', 'start'];

                if (Ide::get()->isWindows()) {
                    $args = flow(['cmd', '/c'], $args)->toArray();
                }

                return [
                    "args" => $args,
                    "dir"  => $project->getRootDir(),
                    "env"  => $env
                ];
            },
        ]);

        $project->getRunDebugManager()->add('jppm-build', [
            'title' => 'jppm.tasks.build.title',
            'prepareFunc' => $prepareFunc,
            'icon' => 'icons/boxArrow16.png',
            'makeStartProcess' => function () use ($project) {
                $env = Ide::get()->makeEnvironment();

                $args = ['jppm', 'build'];

                if (Ide::get()->isWindows()) {
                    $args = flow(['cmd', '/c'], $args)->toArray();
                }

                return [
                    "args" => $args,
                    "dir"  => $project->getRootDir(),
                    "env"  => $env
                ];
            },
        ]);
    }

    /**
     * @param Project $project
     * @return mixed
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