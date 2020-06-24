<?php


namespace ide\project\supports\jppm\tasks;

use ide\Ide;
use ide\tasks\TaskProcessInfo;

class JPPMTaskProcessInfo extends TaskProcessInfo {

    /**
     * JPPMTaskProcessInfo constructor.
     * @param string $task
     * @param array $flags
     */
    public function __construct(string $task, array $flags = []) {
        $environment = Ide::get()->makeEnvironment();

        $program = ['jppm', $task];

        if ($flags) {
            $program = flow(
                $program,
                flow($flags)->map(function ($o) { return "-$o"; })
            )->toArray();
        }

        if (Ide::get()->isWindows()) {
            $program = flow(['cmd', '/c'], $program)->toArray();
        }

        parent::__construct($program, $environment);
    }
}
