<?php
namespace ide\project;

use framework\core\Component;
use framework\core\Event;
use ide\tasks\AbstractTaskConfiguration;

/**
 * Class ProjectRunDebugManager
 * @package ide\project
 */
class ProjectRunDebugManager extends Component
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
        parent::__construct();

        $this->project = $project;
    }

    /**
     * @param string $code
     * @param AbstractTaskConfiguration $config
     */
    public function add(string $code, AbstractTaskConfiguration $config)
    {
        $this->items[$code] = $config;
        $this->trigger(new Event('add', $this, null, ['code' => $code, 'config' => $config]));
        $this->trigger(new Event('change', $this));
    }

    public function get(string $code): ?array
    {
        return $this->items[$code];
    }

    /**
     * @return AbstractTaskConfiguration[]
     */
    public function getItems(): array {
        return $this->items;
    }

    public function has(string $code): bool
    {
        return isset($this->items[$code]);
    }

    public function remove(string $code)
    {
        unset($this->items[$code]);

        $this->trigger(new Event('remove', $this, null, ['code' => $code]));
        $this->trigger(new Event('change', $this));
    }

    public function clear()
    {
        $this->items = [];
        $this->trigger(new Event('change', $this));
    }
}
