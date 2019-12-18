<?php


namespace ide\project\supports\jppm\tasks;

use ide\Ide;
use ide\tasks\TaskProcessInfo;

class JPPMTaskProcessInfo extends TaskProcessInfo {

    /**
     * JPPMTaskProcessInfo constructor.
     * @param string $task
     * @throws \Exception
     */
    public function __construct(string $task) {
        $environment = Ide::get()->makeEnvironment();

        $program = ['jppm', $task];

        if (Ide::get()->isWindows()) {
            $program = flow(['cmd', '/c'], $program)->toArray();
        }

        parent::__construct($program, $environment);
    }
}