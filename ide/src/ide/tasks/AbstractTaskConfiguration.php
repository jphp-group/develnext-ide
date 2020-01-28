<?php

namespace ide\tasks;

use ide\project\Project;

abstract class AbstractTaskConfiguration {

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    public function getIcon(): string {
        return "icons/run16.png";
    }

    /**
     * @return string
     */
    public function getDefaultEnvironment(): string {
        return Project::ENV_DEV;
    }

    /**
     * @return array
     */
    public function getPreExecuteIdeTasks(): array {
        return [];
        //return ["preCompile", "compile"];
    }

    /**
     * @return TaskProcessInfo
     */
    abstract public function getTaskInfo(): TaskProcessInfo;

    /**
     * @param int $exitCode
     */
    public function onProcessExit(int $exitCode) {
        // Stub ..
    }
}
