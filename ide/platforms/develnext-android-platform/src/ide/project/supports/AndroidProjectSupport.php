<?php

namespace ide\project\supports;

use ide\commands\AndroidSettingsCommand;
use ide\Ide;
use ide\project\AbstractProjectSupport;
use ide\project\Project;
use ide\project\templates\AndroidProjectTemplate;
use php\gui\UXDialog;

class AndroidProjectSupport extends AbstractProjectSupport {

    /**
     * @param Project $project
     * @return mixed
     * @throws \Exception
     */
    public function isFit(Project $project) {
        return $project->getTemplate() instanceof AndroidProjectTemplate;
    }

    /**
     * @param Project $project
     * @return mixed
     */
    public function onLink(Project $project) {
        $project->getRunDebugManager()->add('jppm-start', [
            'title' => 'jppm.tasks.start.android.title',
            'makeStartProcess' => function () use ($project) {
                $env = Ide::get()->makeEnvironment();

                $env['JAVA_HOME'] = AndroidSettingsCommand::getJDKDir();

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
            'title' => 'jppm.tasks.build.android.title',
            'icon' => 'icons/android16.png',
            'makeStartProcess' => function () use ($project) {
                $env = Ide::get()->makeEnvironment();

                $env['ANDROID_HOME'] = AndroidSettingsCommand::getSDKDir();
                $env['JAVA_HOME'] = AndroidSettingsCommand::getJDKDir();

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
    public function onUnlink(Project $project) {

    }
}