<?php
namespace ide\project\supports;

use ide\formats\templates\JPPMPackageFileTemplate;
use ide\Ide;
use ide\project\AbstractProjectSupport;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\Project;
use ide\systems\ProjectSystem;
use php\lang\Process;
use php\lib\fs;

/**
 * Class JPPMProjectSupport
 * @package ide\project\supports
 */
class JPPMProjectSupport extends AbstractProjectSupport
{
    /**
     * @var JPPMPackageFileTemplate
     */
    protected $pkgTemplate;

    /**
     * @param Project $project
     * @return bool
     */
    public function isFit(Project $project)
    {
        return $project->hasBehaviour(PhpProjectBehaviour::class)
            || $project->getFile("package.php.yml")->isFile();
    }

    /**
     * @param Project $project
     */
    public function onLink(Project $project)
    {
        $project->getTree()->addIgnorePaths([
            'package-lock.php.yml'
        ]);

        $this->pkgTemplate = new JPPMPackageFileTemplate($project->getFile('package.php.yml'));

        $project->on('changeName', function ($oldName, $newName) {
            $this->pkgTemplate->setName($newName);
            $this->pkgTemplate->save();
        }, __CLASS__);

        $project->on('save', function () {
            $this->pkgTemplate->save();
        }, __CLASS__);

        $this->pkgTemplate->setSources(['src', 'src_generated']);
        $project->setSrcDirectory('src');
        $project->setSrcGeneratedDirectory('src_generated');

        $project->getRunDebugManager()->add('start', [
            'title' => 'Запустить',
            'makeStartProcess' => function () use ($project) {
                $process = new Process(['cmd', '/c', 'jppm', 'app:run', '-l'], $project->getRootDir(), Ide::get()->makeEnvironment());
                return $process;
            },
        ]);

        $this->install($project);
    }

    public function getVendorInspectDirs(Project $project)
    {
        $result = [];
        $dirs = fs::scan("{$project->getRootDir()}/vendor", ['excludeFiles' => true], 1);

        foreach ($dirs as $dir) {
            $pkgFile = "$dir/package.php.yml";

            if (fs::isFile($pkgFile)) {
                $pkgData = fs::parse($pkgFile);

                if (is_array($pkgData['sources'])) {
                    foreach ($pkgData['sources'] as $src) {
                        if (fs::isDir("$dir/$src")) {
                            $result["$dir/$src"] = "$dir/$src";
                        }
                    }
                }

                $sdkDir = "$dir/sdk";

                if (fs::isDir($sdkDir)) {
                    $result[$sdkDir] = $sdkDir;
                }
            }
        }

        return $result;
    }

    public function install(Project $project)
    {
        $oldInspectDirs = $this->getVendorInspectDirs($project);

        $process = (new Process(['cmd', '/c', 'jppm', 'install'], $project->getRootDir(), Ide::get()->makeEnvironment()))
            ->inheritIO()->startAndWait();

        $newInspectDirs = $this->getVendorInspectDirs($project);
        foreach ($newInspectDirs as $dir) {
            $project->loadDirectoryForInspector($dir);
        }

        foreach ($oldInspectDirs as $dir) {
            if (!$newInspectDirs[$dir]) {
                $project->unloadDirectoryForInspector($dir);
            }
        }
    }

    public function addDep(string $name, string $version = '*')
    {
        $this->pkgTemplate->setDeps(flow($this->pkgTemplate->getDeps(), [$name => $version])->toMap());
    }

    public function removeDep(string $name)
    {
        $deps = $this->pkgTemplate->getDeps();
        unset($deps[$name]);

        $this->pkgTemplate->setDeps($deps);
    }

    public function hasDep(string $name): bool
    {
        return isset($this->pkgTemplate->getDeps()[$name]);
    }

    /**
     * @param Project $project
     * @throws \Exception
     */
    public function onUnlink(Project $project)
    {
        $project->getTree()->removeIgnorePaths(['package-lock.php.yml']);
        $project->offGroup(__CLASS__);

        $this->pkgTemplate->save();
        $this->pkgTemplate = null;

        foreach ($this->getVendorInspectDirs($project) as $dir) {
            $project->unloadDirectoryForInspector($dir);
        }
    }

    public function getCode()
    {
        return 'jppm';
    }
}