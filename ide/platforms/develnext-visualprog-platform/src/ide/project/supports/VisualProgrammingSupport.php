<?php
namespace ide\project\supports;

use ide\action\ActionManager;
use ide\Logger;
use ide\project\AbstractProjectSupport;
use ide\project\Project;

class VisualProgrammingSupport extends AbstractProjectSupport
{
    public function getCode()
    {
        return 'visprog';
    }

    public function getFitRequiredSupports(): array
    {
        return ['javafx'];
    }

    /**
     * @inheritDoc
     */
    public function isFit(Project $project)
    {
        return $project->hasSupport('javafx');
    }

    /**
     * @inheritDoc
     */
    public function onLink(Project $project)
    {
        $tree = $project->getTree();
        $tree->addIgnoreExtensions(['axml']);

        $project->on('preCompile', fn($env, $log = null) => $this->onProjectPreCompile($project, $env, $log));

        $actionManager = new ActionManager();
        $project->data(self::class . '#actionManager', $actionManager);

        $this->registerActions($project, '.dn/visprog/actionTypes');
    }

    /**
     * @inheritDoc
     */
    public function onUnlink(Project $project)
    {
        $tree = $project->getTree();
        $tree->removeIgnoreExtensions(['axml']);

        $this->unregisterActions($project, '.dn/visprog/actionTypes');

        $this->getActionManager($project)->free();
        $project->data(self::class . '#actionManager', null);
    }

    public function registerActions(Project $project, $source)
    {
        $actionManager = $this->getActionManager($project);

        if ($actionManager) {
            $actionManager->registerInternalList($source);
        }
    }

    public function unregisterActions(Project $project, $source)
    {
        $actionManager = $this->getActionManager($project);

        if ($actionManager) {
            $actionManager->unregisterInternalList($source);
        }
    }

    /**
     * @param Project $project
     * @param $env
     * @param callable|null $log
     * @throws \Exception
     */
    protected function onProjectPreCompile(Project $project, $env, callable $log = null)
    {
        $withSourceMap = $env == Project::ENV_DEV;

        $srcDir = $project->getSrcFile('');
        $srcGenDir = $project->getSrcFile('', true);

        $this->getActionManager($project)->compile($srcDir, $srcGenDir, function ($filename) use ($log, $project) {
            $name = $project->getAbsoluteFile($filename)->getRelativePath();

            Logger::info("Apply actions for '$name'");
            if ($log) {
                $log(':apply actions "' . $name . '"');
            }
        }, $withSourceMap);
    }

    public function getActionManager(Project $project): ?ActionManager
    {
        return $project->data(self::class . '#actionManager');
    }
}
