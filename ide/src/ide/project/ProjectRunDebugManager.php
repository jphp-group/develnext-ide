<?php
namespace ide\project;

/**
 * Class ProjectRunDebugManager
 * @package ide\project
 */
class ProjectRunDebugManager
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var array
     */
    private $items = [];

    /**
     * ProjectRunDebugManager constructor.
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param string $code
     * @param array $config
     */
    public function add(string $code, array $config)
    {
        $this->items[$code] = $config;
    }

    public function get(string $code): ?array
    {
        return $this->items[$code];
    }

    public function has(string $code): bool
    {
        return isset($this->items[$code]);
    }

    public function remove(string $code)
    {
        unset($this->items[$code]);
    }
}
