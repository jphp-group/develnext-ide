<?php

namespace ide\project\supports\jppm\tasks;

use ide\tasks\AbstractTaskConfiguration;
use ide\tasks\TaskProcessInfo;

class JPPMStartTaskConfiguration extends AbstractTaskConfiguration {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return "jppm.tasks.start.title";
    }

    /**
     * @inheritDoc
     */
    public function getTaskInfo(): TaskProcessInfo {
        return new JPPMTaskProcessInfo("start");
    }
}
