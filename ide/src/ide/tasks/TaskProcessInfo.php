<?php

namespace ide\tasks;

use ide\Ide;
use php\io\File;

class TaskProcessInfo {

    /**
     * @var array
     */
    private $program;

    /**
     * @var array
     */
    private $environment;

    /**
     * @var File
     */
    private $directory;

    /**
     * TaskProcessInfo constructor.
     * @param array $program
     * @param array $environment
     * @param File $directory
     */
    public function __construct(array $program, array $environment = [], File $directory = null)
    {
        $this->program = $program;
        $this->environment = $environment;

        if ($directory)
            $this->directory = $directory;
        else
            $this->directory = new File(Ide::project()->getRootDir());
    }

    /**
     * @return array
     */
    public function getProgram(): array
    {
        return $this->program;
    }

    /**
     * @return array
     */
    public function getEnvironment(): array
    {
        return $this->environment;
    }

    /**
     * @return File
     */
    public function getDirectory(): File
    {
        return $this->directory;
    }
}
