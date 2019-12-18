<?php

namespace ide\tasks;

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
