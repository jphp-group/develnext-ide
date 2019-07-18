<?php
namespace ide\project\supports;

use Throwable;
use framework\core\Event;
use function alert;
use function pre;
use function uiLater;
use function var_dump;
use ide\Ide;
use ide\Logger;
use ide\bundle\AbstractJarBundle;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\misc\FileWatcher;
use ide\project\AbstractProjectSupport;
use ide\project\Project;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\control\CommonProjectControlPane;
use ide\systems\IdeSystem;
use ide\systems\ProjectSystem;
use ide\ui\Notifications;
use php\concurrent\Promise;
use php\io\IOException;
use php\lang\Process;
use php\lang\System;
use php\lib\arr;
use php\lib\fs;
use php\lib\reflect;
use php\lib\str;
use timer\AccurateTimer;

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

    /**
     * Установка пакетов и бандлов через jppm в проект. Создаёт новые файлы в текущем проекте.
     * @param  Project       $project   
     * @param  callable|null $onComplete Коллбек будет вызван по завершению установки
     * @param  callable|null $onError(string $errorText)    Коллбек будет вызван при возникновении ошибок.
     */
    public function install(Project $project, ?callable $onComplete = null, ?callable $onError = null)
    {
        $project->loadDirectoryForInspector(IdeSystem::getOwnFile("stubs/dn-php-stub"));
        $project->loadDirectoryForInspector(IdeSystem::getOwnFile("stubs/dn-jphp-stub"));

        $promisses = [];
        foreach (fs::scan($project->getFile("vendor/"), ['excludeFiles' => true]) as $dir) {
            $pkgName = fs::name($dir);

            if (!$this->pkgTemplate->getDeps()[$pkgName]) {
                foreach ($this->getVendorInspectDirsForDep($project, $pkgName) as $inspectDir) {
                    $promisses[] = $project->unloadDirectoryForInspector($inspectDir);
                }
            }
        }

        Promise::all($promisses)->then(function () use ($project, $onComplete, $onError) {
            $args = ['jppm', 'install'];

            if (Ide::get()->isWindows()) {
                $args = flow(['cmd', '/c'], $args)->toArray();
            }

            $process = (new Process($args, $project->getRootDir(), Ide::get()->makeEnvironment()));
            if(!is_callable($onError)){
                $process = $process->inheritIO()->startAndWait();
            } else {
                // Если есть callback для ошибок, забираем себе output
                $process = $process->startAndWait();
                $jppmOutpput = $process->getInput()->readFully();
                Logger::debug('Installing result: ' . $jppmOutpput);

                // Если удаляется плагин, develnext блокирует некоторые файлы, они не будут удалены, но на процесс сборки абсолютно не влияют.
                if(str::posIgnoreCase($jppmOutpput, 'failed') > -1){
                    Logger::error('Plugin install error');
                    uiLater(function() use ($onError, $jppmOutpput){ call_user_func($onError, $jppmOutpput); });
                }
            }
            
            $newInspectDirs = $this->getVendorInspectDirs($project);
            foreach ($newInspectDirs as $dir) {
                $project->loadDirectoryForInspector($dir);
            }
            if(is_callable($onComplete)) uiLater(function() use ($onComplete){ call_user_func($onComplete); });
        })->catch(function (Throwable $e) use ($onError, $onComplete){
            Logger::exception("Failed to install", $e);
            if(is_callable($onError)) uiLater(function() use ($onError, $e){ call_user_func($onError, $e->getMessage()); });
            if(is_callable($onComplete)) uiLater(function() use ($onComplete){ call_user_func($onComplete); });
        });
    }

    /**
     * Установка зависимостей jppm в среду. Парсит структуру пакетов, их зависимости. Добавляет пути к новым классам и удаляет неиспользуемые.
     * @param  Project $project
     */
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
                        if (isset($data['class'])) {
                            $bundleClass = $data['class'];
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

    /**
     * Добавить пакет в зависимости
     * @param string $name
     * @param string $version
     */
    public function addDep(string $name, string $version = '*') {
        $this->pkgTemplate->load();
        $this->pkgTemplate->addDep($name, $version);
        $this->pkgTemplate->save();
    }

    /**
     * Удалить пакет из зависимостей
     * @param  string $name
     */
    public function removeDep(string $name)
    {
        $deps = $this->pkgTemplate->getDeps();
        unset($deps[$name]);

        $this->pkgTemplate->setDeps($deps);
        $this->pkgTemplate->save();
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

    public function getDepConfig(string $dep, ?Project $project = null): array {
        $project = is_null($project) ? Ide::project() : $project;
        $packageFile = $project->getRootDir() . "/vendor/" . $dep . "/package.php.yml";
        if(fs::exists($packageFile)){
            return fs::parse($packageFile);
        }

        return [];
    }
}