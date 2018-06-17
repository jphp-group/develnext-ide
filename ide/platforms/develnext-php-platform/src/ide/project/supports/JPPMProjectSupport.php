<?php
namespace ide\project\supports;

use function alert;
use framework\core\Event;
use framework\core\Promise;
use ide\bundle\AbstractBundle;
use ide\bundle\AbstractJarBundle;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\Ide;
use ide\Logger;
use ide\misc\FileWatcher;
use ide\project\AbstractProjectSupport;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\control\CommonProjectControlPane;
use ide\project\Project;
use ide\systems\IdeSystem;
use ide\systems\ProjectSystem;
use ide\ui\Notifications;
use php\io\IOException;
use php\lang\Process;
use php\lang\System;
use php\lib\arr;
use php\lib\fs;
use php\lib\reflect;
use function pre;
use Throwable;
use timer\AccurateTimer;
use function uiLater;
use function var_dump;

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
     * @var FileWatcher
     */
    protected $pkgFileWatcher;

    /**
     * @var array
     */
    protected $projectIdeBundles = [];

    /**
     * @var array
     */
    protected $allIdeBundles = [];

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
     * @return mixed|void
     */
    public function onLink(Project $project)
    {
        $project->getTree()->addIgnorePaths([
            'package-lock.php.yml'
        ]);

        $pkgFile = $project->getFile('package.php.yml');
        $this->pkgTemplate = new JPPMPackageFileTemplate($pkgFile);
        $this->pkgFileWatcher = new FileWatcher($pkgFile);

        $this->pkgFileWatcher->on('change', function (Event $event) use ($project) {
            if ($event->data['newTime'] >= 0) {
                $oldDeps = $this->pkgTemplate->getDeps();
                $oldDevDeps = $this->pkgTemplate->getDevDeps();
                $oldPlugins = $this->pkgTemplate->getPlugins();

                $this->pkgTemplate->load();

                $newDeps = $this->pkgTemplate->getDeps();
                $newDevDeps = $this->pkgTemplate->getDevDeps();
                $newPlugins = $this->pkgTemplate->getPlugins();

                if ($oldDeps != $newDeps || $oldDevDeps != $newDevDeps || $oldPlugins != $newPlugins) {
                    $this->install($project);
                    $this->installToIDE($project);

                    $project->refreshSupports();
                }
            }
        });

        $project->on('changeName', function ($oldName, $newName) {
            $this->pkgTemplate->setName($newName);
            $this->pkgTemplate->save();
        }, __CLASS__);

        $project->on('save', function () {
            //$this->pkgTemplate->save();
        }, __CLASS__);

        $this->pkgTemplate->setSources(['src_generated', 'src']);
        $project->setSrcDirectory('src');
        $project->setSrcGeneratedDirectory('src_generated');

        if ($project->getSrcFile("JPHP-INF/launcher.conf")->exists()) {
            fs::delete($project->getSrcFile("JPHP-INF/launcher.conf"));
        }

        $this->pkgTemplate->save();

        $this->install($project);
        $this->installToIDE($project);

        $this->pkgFileWatcher->start();
    }

    public function getVendorInspectDirsForDep(Project $project, string $depName)
    {
        $result = [];

        $dir = "{$project->getRootDir()}/vendor/$depName";
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

        return $result;
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
        $project->loadDirectoryForInspector(IdeSystem::getOwnFile("stubs/dn-php-stub"));
        $project->loadDirectoryForInspector(IdeSystem::getOwnFile("stubs/dn-jphp-stub"));

        $promisses = [];
        foreach (fs::scan("{$project->getFile("vendor/")}", ['excludeFiles' => true]) as $dir) {
            $pkgName = fs::name($dir);

            if (!$this->pkgTemplate->getDeps()[$pkgName]) {
                foreach ($this->getVendorInspectDirsForDep($project, $pkgName) as $inspectDir) {
                    $promisses[] = $project->unloadDirectoryForInspector($inspectDir);
                }
            }
        }

        Promise::all($promisses)->then(function () use ($project) {
            $process = (new Process(['cmd', '/c', 'jppm', 'install'], $project->getRootDir(), Ide::get()->makeEnvironment()))
                ->inheritIO()->startAndWait();

            $newInspectDirs = $this->getVendorInspectDirs($project);

            foreach ($newInspectDirs as $dir) {
                $project->loadDirectoryForInspector($dir);
            }
        })->catch(function (Throwable $e) {
            Logger::exception("Failed to install", $e);
        });
    }

    public function installToIDE(Project $project)
    {
        foreach (fs::scan("{$project->getRootDir()}/vendor", ['excludeFiles' => true], 1) as $dep) {
            $dep = fs::name($dep);

            if (fs::isFile("{$project->getRootDir()}/vendor/{$dep}/package.php.yml")) {
                $pkgData = fs::parse("{$project->getRootDir()}/vendor/{$dep}/package.php.yml");

                if ($data = $pkgData['ide-bundle']) {
                    if (!$this->allIdeBundles[$dep]) {
                        $this->allIdeBundles[$dep] = $data;
                        System::addClassPath("{$project->getRootDir()}/vendor/{$dep}/src");
                    }

                    if (!$this->projectIdeBundles[$dep]) {
                        $bundleClass = $data['class'];

                        if ($bundleClass) {
                            Logger::info("Add jar bundle: $dep -> $bundleClass");

                            /** @var AbstractJarBundle $bundle */
                            $bundle = new $bundleClass();
                            $bundle->onAdd($project);
                            $data['bundle'] = $bundle;
                        }

                        $this->projectIdeBundles[$dep] = $data;
                    }
                }
            }
        }

        $projectIdeBundles = $this->projectIdeBundles;

        foreach ($projectIdeBundles as $dep => $data) {
            if (!$this->pkgTemplate->getDeps()[$dep]) {
                if ($bundle = $data['bundle']) {
                    Logger::info("Remove jar bundle: $dep -> " . reflect::typeOf($bundle));

                    $bundle->onRemove($project);
                    unset($projectIdeBundles[$dep]);
                }
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
     * @return FileWatcher
     */
    public function getPkgFileWatcher(): FileWatcher
    {
        return $this->pkgFileWatcher;
    }

    /**
     * @return JPPMPackageFileTemplate
     */
    public function getPkgTemplate(): JPPMPackageFileTemplate
    {
        return $this->pkgTemplate;
    }

    /**
     * @param Project $project
     * @return mixed|void
     * @throws \Exception
     */
    public function onUnlink(Project $project)
    {
        $project->getTree()->removeIgnorePaths(['package-lock.php.yml']);
        $project->offGroup(__CLASS__);

        $this->pkgTemplate->save();

        foreach ($this->getVendorInspectDirs($project) as $dir) {
            $project->unloadDirectoryForInspector($dir);
        }

        $project->unloadDirectoryForInspector(IdeSystem::getOwnFile("stubs/dn-php-stub"));
        $project->unloadDirectoryForInspector(IdeSystem::getOwnFile("stubs/dn-jphp-stub"));

        $projectIdeBundles = $this->projectIdeBundles;

        foreach ($projectIdeBundles as $dep => $data) {
            if (!$this->pkgTemplate->getDeps()[$dep]) {
                if ($bundle = $data['bundle']) {
                    $bundle->onRemove($project);
                }
            }
        }

        $this->projectIdeBundles = [];
        $this->pkgTemplate = null;
        $this->pkgFileWatcher->free();
        $this->pkgFileWatcher = null;
    }

    public function getCode()
    {
        return 'jppm';
    }
}