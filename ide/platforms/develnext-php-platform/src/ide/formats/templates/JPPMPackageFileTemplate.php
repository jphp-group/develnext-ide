<?php
namespace ide\formats\templates;

use ide\Logger;
use ide\misc\AbstractMetaTemplate;
use ide\project\Project;
use php\format\ProcessorException;
use php\format\YamlProcessor;
use php\io\FileStream;
use php\io\IOException;
use php\io\Stream;
use php\lang\System;
use php\lib\fs;

class JPPMPackageFileTemplate extends AbstractMetaTemplate
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $version = '1.0.0';

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $sources = [];

    /**
     * @var array
     */
    private $includes = [];

    /**
     * @var array
     */
    private $deps = [];

    private $deps_win = [];
    private $deps_unix = [];
    private $deps_mac = [];

    /**
     * @var array
     */
    private $devDeps = [];

    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @var array
     */
    private $extra = [];

    /**
     * @param Project $project
     */
    public function useProject(Project $project)
    {
        $this->name = $project->getName();
        $this->type = 'project';

        $sources = [];

        if ($project->getSrcDirectory()) {
            $sources[] = $project->getSrcDirectory();
        }

        if ($project->getSrcGeneratedDirectory()) {
            $sources[] = $project->getSrcGeneratedDirectory();
        }

        $this->sources = $sources;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @param array $sources
     */
    public function setSources($sources): void
    {
        $this->sources = (array) $sources;
    }

    /**
     * @return array
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @param array $includes
     */
    public function setIncludes($includes): void
    {
        $this->includes = (array) $includes;
    }

    /**
     * @param string|null $os
     * @return array
     */
    public function getDeps(string $os = null): array
    {
        if ($os) {
            if ($os == "linux") $os = "unix";

            return $this->{"deps_{$os}"};
        } else {
            return $this->deps;
        }
    }

    /**
     * @param array $deps
     * @param string|null $os
     */
    public function setDeps($deps, string $os = null): void
    {
        if ($os) {
            if ($os == "linux") $os = "unix";

            $this->{"deps_{$os}"} = (array) $deps;
        } else {
            $this->deps = (array) $deps;
        }

        $this->sortDeps();
    }

    /**
     * @param string $name
     * @param string $version
     */
    public function addDep($name, $version): void
    {
        $this->deps[$name] = $version;
        $this->sortDeps();
    }

    protected function sortDeps(){
        $this->deps = flow($this->deps)->toMap();
        $this->deps_mac = flow($this->deps_mac)->toMap();
        $this->deps_unix = flow($this->deps_unix)->toMap();
        $this->deps_win = flow($this->deps_win)->toMap();
    }

    /**
     * @return array
     */
    public function getDevDeps(): array
    {
        return $this->devDeps;
    }

    /**
     * @param array $devDeps
     */
    public function setDevDeps($devDeps): void
    {
        $this->devDeps = (array) $devDeps;
    }

    /**
     * @return array
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @param array $plugins
     */
    public function setPlugins($plugins): void
    {
        $this->plugins = (array) $plugins;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @param array $extra
     */
    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
    }

    /**
     * @return array
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @param array $tasks
     */
    public function setTasks(array $tasks): void
    {
        $this->tasks = $tasks;
    }


    public function render(Stream $out)
    {
        $data = [
            'name' => $this->name
        ];

        if ($this->type) $data['type'] = $this->type;
        if (isset($this->version)) $data['version'] = $this->version;
        if ($this->description) $data['description'] = $this->description;
        if ($this->plugins) $data['plugins'] = $this->plugins;

        if ($this->deps) $data['deps'] = $this->deps;
        if ($this->deps_unix) $data['depsUnix'] = $this->deps_unix;
        if ($this->deps_win) $data['depsWin'] = $this->deps_win;
        if ($this->deps_mac) $data['depsMac'] = $this->deps_mac;
        if ($this->devDeps) $data['devDeps'] = $this->devDeps;

        if ($this->sources) $data['sources'] = $this->sources;
        if ($this->includes) $data['includes'] = $this->includes;

        if ($this->tasks) $data['tasks'] = $this->tasks;

        if ($this->extra) {
            $data = flow($data, $this->extra)->toMap();
        }

        $out->writeFormatted($data, 'yaml', YamlProcessor::SERIALIZE_PRETTY_FLOW);
    }

    public function setProperties(array $props) {
        foreach ($props as $key => $value) {
            if (method_exists($this, "set$key")) {
                $this->{"set$key"}($value);
            } else {
                $this->extra[$key] = $value;
            }
        }
    }

    public function load()
    {
        if (fs::isFile($this->file)) {
            try {
                $this->setProperties(fs::parseAs($this->file, "yaml"));
            } catch (ProcessorException | IOException $e) {
                Logger::warn("Failed to load $this->metaFile, {$e->getMessage()}");
            }
        }
    }

    public function save()
    {
        if (!$this->file) {
            throw new \Exception("Unable to save, file is not assigned");
        }

        fs::ensureParent($this->file);

        $out = new FileStream($this->file, "w+");
        try {
            $this->render($out);
        } finally {
            $out->close();
        }
    }
}