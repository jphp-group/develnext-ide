<?php

namespace ide\project\supports\jppm\tasks;

use ide\project\Project;
use ide\tasks\AbstractTaskConfiguration;
use ide\tasks\TaskProcessInfo;

class JPPMStartTaskConfiguration extends AbstractTaskConfiguration {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return "jppm.tasks.start.title";
    }

    public function getDefaultEnvironment(): string {
        return Project::ENV_DEV;
    }

    /**
     * @return array
     */
    public function getPreExecuteIdeTasks(): array {
        return ["preCompile", "compile"];
    }

    /**
     * @inheritDoc
     */
    public function getTaskInfo(): TaskProcessInfo {
        return new JPPMTaskProcessInfo("start", ['light']);
    }
}
