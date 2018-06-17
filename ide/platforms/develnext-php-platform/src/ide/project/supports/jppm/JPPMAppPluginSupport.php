<?php
namespace ide\project\supports\jppm;

use framework\core\Event;
use ide\Ide;
use ide\project\AbstractProjectSupport;
use ide\project\Project;
use ide\project\supports\JPPMProjectSupport;
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
            'title' => 'Запустить',
            'prepareFunc' => $prepareFunc,
            'makeStartProcess' => function () use ($project) {
                $env = Ide::get()->makeEnvironment();
                $process = new Process(['cmd', '/c', 'jppm', 'start'], $project->getRootDir(), $env);
                return $process;
            },
        ]);

        $project->getRunDebugManager()->add('jppm-build', [
            'title' => 'Собрать',
            'prepareFunc' => $prepareFunc,
            'icon' => 'icons/boxArrow16.png',
            'makeStartProcess' => function () use ($project) {
                $env = Ide::get()->makeEnvironment();
                $process = new Process(['cmd', '/c', 'jppm', 'build'], $project->getRootDir(), $env);
                return $process;
            },
        ]);
    }

    /**
     * @param Project $project
     * @return mixed
     */
    public function onUnlink(Project $project)
    {
        $project->getRunDebugManager()->remove('jppm-start');
        $project->getRunDebugManager()->remove('jppm-build');
    }
}