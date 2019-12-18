<?php

namespace ide\project\supports\jppm\tasks;

use ide\forms\BuildSuccessForm;
use ide\Ide;
use ide\tasks\AbstractTaskConfiguration;
use ide\tasks\TaskProcessInfo;
use php\lang\Process;
use php\lib\fs;

class JPPMBuildTaskConfiguration extends AbstractTaskConfiguration {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return "jppm.tasks.build.title";
    }

    /**
     * @return string
     */
    public function getIcon(): string {
        return "icons/boxArrow16.png";
    }

    /**
     * @inheritDoc
     */
    public function getTaskInfo(): TaskProcessInfo {
        return new JPPMTaskProcessInfo("build");
    }

    public function onProcessExit(int $exitCode) {
        if ($exitCode != 0) return;

        $path = fs::normalize(Ide::project()->getRootDir() . "/build/");

        $form = new BuildSuccessForm();
        $form->setBuildPath($path);
        $form->setOpenDirectory($path);

        fs::scan($path, function (string $file) use ($form) {
            if (fs::ext($file) == "jar") {
                $form->setRunProgram($file);

                $form->setOnRun(function () use ($file, $form) {
                    $program = ['java', '-jar', $file];

                    if (Ide::get()->isWindows()) {
                        $program = flow(['cmd', '/c'], $program)->toArray();
                    }

                    (new Process($program))->start();

                    $form->hide();
                });
            }
        });

        $form->show();
    }
}
