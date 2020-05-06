<?php
namespace ide\project\behaviours;

use ide\project\AbstractProjectBehaviour;
use ide\utils\FileUtils;
use php\lib\arr;
use php\lib\fs;

/**
 * Class RunBuildProjectBehaviour
 * @package ide\project\behaviours
 */
class RunBuildProjectBehaviour extends AbstractProjectBehaviour
{
    /**
     * ...
     */
    public function inject()
    {
    }

    /**
     * @return array
     */
    public function getSourceDirectories()
    {
        $result = [];

        if ($project = $this->project) {
            foreach ($project->getModules() as $module) {
                if ($module->isDir()) {
                    $result[$module->getId()] = $module->getId();
                }
            }
        }

        $result[] = 'src_generated/';
        $result[] = 'src/';

        return $result;
    }

    /**
     * @param array $types extensions
     * @return array
     */
    public function getProfileModules(array $types)
    {
        $result = [];

        if ($project = $this->project) {
            foreach ($project->getModules() as $module) {
                if ($module->isDir()) continue;

                switch ($module->getType()) {
                    default:
                        if (fs::exists($module->getId())) {
                            if (fs::isFile($module->getId())) {
                                $result[] = fs::abs($module->getId());
                            }
                        }

                        break;
                }
            }
        }

        $new = [];

        foreach ($result as $one) {
            if (arr::has($types, fs::ext($one))) {
                $new[] = FileUtils::adaptName($one);
            }
        }

        return $new;
    }

    /**
     * see PRIORITY_* constants
     * @return int
     */
    public function getPriority()
    {
        return self::PRIORITY_COMPONENT;
    }
}