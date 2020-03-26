<?php
namespace ide\project;

use develnext\lexer\inspector\AbstractInspector;
use Exception;
use ide\editors\menu\ContextMenu;
use ide\formats\AbstractFileTemplate;
use ide\formats\IdeFormatOwner;
use ide\forms\MainForm;
use ide\Ide;
use ide\IdeConfiguration;
use ide\IdeException;
use ide\Logger;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\concurrent\Promise;
use php\io\File;
use php\lang\ThreadPool;
use php\lib\arr;
use php\lib\fs;
use php\lib\reflect;
use php\lib\Str;
use php\time\Time;
use php\time\Timer;
use php\util\Flow;
use script\TimerScript;
use Throwable;

/**
 * Class Project
 * @package ide\project
 */
class Project
{
    use IdeFormatOwner;

    const ENV_ALL  = 'all';
    const ENV_DEV  = 'dev';
    const ENV_PROD = 'prod';
    const ENV_TEST = 'test';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $packageName = 'app';

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string[]
     */
    protected $sourceRoots = [];

    /**
     * @var ProjectModule[]
     */
    protected $modules = [];

    /**
     * @var callable
     */
    protected $moduleTypeHandlers = [];

    /**
     * @var AbstractProjectBehaviour[]
     */
    protected $behaviours = [];

    /**
     * @var AbstractProjectSupport[]
     */
    protected $supports = [];

    /**
     * @var Timer
     */
    protected $supportRefreshTimer;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @var array
     */
    protected $ignoreRules = [];

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var IdeConfiguration[]
     */
    protected $ideConfigs = [];

    /**
     * @var ProjectTree
     */
    protected $tree;

    /**
     * @var AbstractProjectTemplate
     */
    protected $template;

    /**
     * @var ProjectIndexer
     */
    protected $indexer;

    /**
     * @var ProjectRefactorManager
     */
    protected $refactorManager;

    /**
     * @var TimerScript
     */
    protected $tickTimer;

    /**
     * @var string
     */
    protected $srcDirectory = null;

    /**
     * @var string
     */
    protected $srcGeneratedDirectory = null;

    /**
     * @var array
     */
    protected array $readOnlyDirectories = [];

    /**
     * @var string
     */
    protected $resDirectory = null;

    /**
     * @var callable[] (IdeConfiguration $config)
     */
    protected $configConfigurers = [];

    /**
     * @var AbstractInspector[]
     */
    protected $inspectors = [];

    /**
     * @var ThreadPool
     */
    protected $inspectorLoaderThreadPoll;

    /**
     * @var callable[]
     */
    private $listHandlers = [];

    /**
     * @var ProjectRunDebugManager
     */
    private $runDebugManager;

    /**
     * @var array
     */
    private $data = [];

    /**
     * Project constructor.
     *
     * @param string $rootDir
     * @param string $name
     * @throws \php\lang\IllegalArgumentException
     */
    public function __construct($rootDir, $name)
    {
        $this->name = $name;
        $this->rootDir = $rootDir;
        $this->config  = new ProjectConfig($rootDir, $name);

        /** @var MainForm $mainForm */
        $mainForm = Ide::get()->getMainForm();

        $this->tree = new ProjectTree($this);
        $this->runDebugManager = new ProjectRunDebugManager($this);
        $this->indexer = new ProjectIndexer($this);
        $this->refactorManager = new ProjectRefactorManager($this);

        $this->tickTimer = new TimerScript(1000 * 9, true, [$this, 'doTick']);
        $this->inspectorLoaderThreadPoll = ThreadPool::createSingle();
    }

    /**
     * @param string $filename
     *                                                                                                                                                                                                                               3
     * @return Project
     * @throws \php\lang\IllegalArgumentException
     */
    public static function createForFile($filename)
    {
        $file = File::of($filename);

        $name = $file->getName();

        if (Str::endsWith($name, '.dnproject')) {
            $name = Str::sub($name, 0, Str::length($name) - 10);
        }

        return new Project($file->getParent(), $name);
    }

    public function getMainProjectFile()
    {
        return $this->config->getConfigPath();
    }

    /**
     * @return ProjectConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $name
     * @param $value
     * @return mixed|null
     */
    public function data(string $name, $value = null)
    {
        if (func_num_args() == 1) {
            return $this->data[$name];
        } else {
            if ($value === null) {
                unset($this->data[$name]);
            } else {
                $this->data[$name] = $value;
            }

            return null;
        }
    }

    public function clearData(string $prefix): array
    {
        $r = [];
        foreach ($this->data as $key => $value) {
            if (str::startsWith($key, $prefix)) {
                $r[] = $key;
                unset($this->data[$key]);
            }
        }

        return $r;
    }

    public function doTick()
    {
        $file = $this->getIdeFile("ide.lock");
        FileUtils::putAsync($file, Time::millis());
    }

    public function getProjectFile()
    {
        return $this->getFile($this->name . ".dnproject");
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param string $newName
     * @return bool
     */
    public function setName($newName)
    {
        if (FileUtils::copyFile($this->getProjectFile(), $this->getFile($newName . ".dnproject")) == -1) {
            return false;
        }

        $this->trigger('changeName', $this->name, $newName);

        $this->getProjectFile()->delete();
        $this->getProjectFile()->deleteOnExit();

        $this->name = $newName;

        $this->config = new ProjectConfig($this->getRootDir(), $newName);

        return true;
    }

    /**
     * @return string
     */
    public function getSrcDirectory()
    {
        return $this->srcDirectory;
    }

    /**
     * @param callable $callback
     */
    public function eachSrcFile(callable $callback)
    {
        fs::scan($this->getSrcFile(''), function ($filename) use ($callback) {
            $file = $this->getAbsoluteFile($filename);

            return $callback($file, $file->getRelativePath($this->getSrcDirectory())) === true;
        });
    }

    public function addReadOnlyDirectory(string $directory)
    {
        $this->readOnlyDirectories[$directory] = $directory;
    }

    public function removeReadOnlyDirectory(string $directory)
    {
        unset($this->readOnlyDirectories[$directory]);
    }

    public function isReadOnlyFile(string $path)
    {
        $path = $this->getAbsoluteFile($path);

        foreach ($this->readOnlyDirectories as $directory) {
            $directory = $this->getFile($directory);

            if (str::startsWith(FileUtils::hashName($path), FileUtils::hashName($directory))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $srcDirectory
     */
    public function setSrcDirectory($srcDirectory)
    {
        $this->srcDirectory = $srcDirectory;
    }

    /**
     * @return string
     */
    public function getSrcGeneratedDirectory()
    {
        return $this->srcGeneratedDirectory;
    }

    /**
     * @param string $srcGeneratedDirectory
     */
    public function setSrcGeneratedDirectory($srcGeneratedDirectory)
    {
        $this->srcGeneratedDirectory = $srcGeneratedDirectory;
    }

    /**
     * @return string
     */
    public function getResDirectory()
    {
        return $this->resDirectory;
    }

    /**
     * @param string $resDirectory
     */
    public function setResDirectory($resDirectory)
    {
        $this->resDirectory = $resDirectory;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function makeDirectory($path)
    {
        $directory = "$this->rootDir/$path";

        Logger::debug("Make directory in project: $directory");

        return File::of($directory)->mkdirs();
    }

    /**
     * @param string $name
     * @param string $formatClass
     * @param array $options
     * @return ProjectFile
     * @throws IdeException
     */
    public function createBlank(string $name, string $formatClass, array $options = [])
    {
        $format = $this->getRegisteredFormat($formatClass);

        if ($format == null) {
            throw new IdeException("Format $formatClass not found");
        }

        return $format->createBlank($this, $name, $options);
    }

    /**
     * @param $file
     * @param AbstractFileTemplate $template
     *
     * @return ProjectFile
     */
    public function createFile($file, AbstractFileTemplate $template)
    {
        $file = $file instanceof ProjectFile ? $file : $this->getFile($file);

        $file->applyTemplate($template);
        $file->updateTemplate(true);

        if (fs::isFile($file)) {
            foreach ($this->inspectors as $inspector) {
                $inspector->loadSource($file);
            }
        }

        return $file;
    }

    /**
     * @param string $file
     * @param AbstractFileTemplate $template
     * @param bool $override
     */
    public function defineFile($file, AbstractFileTemplate $template, $override = false)
    {
        $file = $this->getFile($file);

        if ($file->isNew() || $override) {
            $file->setGenerated(true);
            $file->applyTemplate($template);
        }

        $file->updateTemplate($override);
    }

    /**
     * @param string $file
     * @return ProjectFile|File
     */
    public function getFile($file)
    {
        return $this->fetchFile("$this->rootDir/$file");
    }

    /**
     * @param string $file
     * @param bool $generated
     * @return ProjectFile|File
     * @throws Exception
     */
    public function getSrcFile($file, $generated = false)
    {
        $srcDirectory = $generated ? $this->srcGeneratedDirectory : $this->srcDirectory;

        if ($srcDirectory === null) {
            throw new Exception(($generated ? "srcGeneratedDirectory" : "srcDirectory") . " is not set");
        }

        return $srcDirectory ? $this->getFile("$srcDirectory/$file") : $this->getFile($file);
    }

    /**
     * @param $file
     *
     * @return ProjectFile|File
     */
    public function getAbsoluteFile($file)
    {
        return $this->fetchFile("$file");
    }

    /**
     * @return array
     */
    public function getIgnoreRules()
    {
        return $this->ignoreRules;
    }

    /**
     * @param array $ignoreRules
     */
    public function setIgnoreRules(array $ignoreRules)
    {
        $this->ignoreRules = $ignoreRules;
    }

    /**
     * @param $type
     * @param callable $handler (ProjectModule $module, $first, $remove)
     */
    public function registerModuleTypeHandler($type, callable $handler)
    {
        $this->moduleTypeHandlers[$type][] = $handler;
    }

    /**
     * @param ProjectModule $module
     * @param $owner
     */
    public function addModule(ProjectModule $module, $owner = 'user')
    {
        if (!$this->modules[$module->getUniqueId()][$owner]) {
            Logger::debug("Add module: " . $module->getUniqueId() . ", owner = $owner");
            $handlers = $this->moduleTypeHandlers[$module->getType()];

            if ($handlers) {
                foreach ($handlers as $handler) {
                    $handler($module, sizeof($this->modules[$module->getType()]) < 1, false, $owner);
                }
            }

            $this->modules[$module->getUniqueId()][$owner] = $module;
        }
    }

    /**
     * @param ProjectModule $module
     * @param string $owner
     * @return bool
     */
    public function hasModule(ProjectModule $module, $owner = 'user')
    {
        return isset($this->modules[$module->getUniqueId()][$owner]);
    }

    /**
     * @param ProjectModule $module
     * @param string $owner
     */
    public function removeModule(ProjectModule $module, $owner = 'user')
    {
        if ($this->modules[$module->getUniqueId()][$owner]) {
            Logger::info("Remove module: " . $module->getUniqueId() . ", owner = $owner");

            $handlers = $this->moduleTypeHandlers[$module->getType()];

            if ($handlers) {
                foreach ($handlers as $handler) {
                    $handler($module, sizeof($this->modules[$module->getType()]) == 1, true, $owner);
                }
            }

            unset($this->modules[$module->getUniqueId()][$owner]);

            if (!$this->modules[$module->getUniqueId()]) {
                unset($this->modules[$module->getUniqueId()]);
            }
        }
    }

    /**
     * @return ProjectModule[]
     */
    public function getModules()
    {
        $result = [];

        foreach ($this->modules as $owner => $modules) {
            $result[] = arr::first($modules);
        }

        return $result;
    }

    /**
     * @param string $toDir
     * @throws Exception
     */
    public function copyModuleFiles($toDir)
    {
        foreach ($this->getModules() as $module) {
            if (!$module->isDir() && fs::isFile($module->getId())) {
                if (FileUtils::copyFile($module->getId(), $toDir ."/". fs::name($module->getId())) == -1) {
                    throw new Exception("Unable to copy {$module->getId()} file");
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * @return IdeConfiguration
     */
    public function getIdeServiceConfig()
    {
        return $this->getIdeConfig('project.ws');
    }

    public function getIdeLibraryConfig()
    {
        return $this->getIdeConfig('library.conf');
    }

    /**
     * @param callable $handler
     */
    public function addIdeConfigConfigurer($id, callable $handler)
    {
        $this->configConfigurers[$id] = $handler;
    }

    /**
     * @param $id
     */
    public function removeIdeConfigConfigurer($id)
    {
        unset($this->configConfigurers[$id]);
    }

    /**
     * @param $name
     * @return IdeConfiguration
     */
    public function getIdeConfig($name)
    {
        if ($configuration = $this->ideConfigs[$name]) {
            return $configuration;
        }

        $configuration = new IdeConfiguration($this->getIdeDir() . "/$name", str::replace($name, "\\", "/"));

        foreach ($this->configConfigurers as $handler) {
            $handler($configuration);
        }

        return $this->ideConfigs[$name] = $configuration;
    }

    /**
     * @return File
     */
    public function getIdeDir()
    {
        return File::of("$this->rootDir/.dn");
    }

    /**
     * @param $name
     * @return File
     */
    public function getIdeFile($name)
    {
        return new File($this->getIdeDir(), "/$name");
    }

    /**
     * @param string $name
     * @return File
     */
    public function getIdeCacheFile($name)
    {
        return $this->getIdeFile("cache/$name");
    }

    /**
     * @param string $group
     * @return bool
     */
    public function clearIdeCache($group = '')
    {
        $file = $this->getIdeCacheFile($group);

        if (!fs::exists($file)) {
            Logger::info("Skip clear cache: $file");
            return false;
        }

        if ($file->isFile()) {
            return fs::delete($file);
        } else {
            return FileUtils::deleteDirectory($file);
        }
    }

    /**
     * @param string $nameList
     * @return array
     */
    public function fetchNamedList($nameList)
    {
        $result = [];

        foreach ((array) $this->listHandlers[$nameList] as $handler) {
            $result = array_merge($result, (array) $handler());
        }

        return $result;
    }

    /**
     * @param string $listName
     * @param callable $handler
     * @param string $group
     * @return string
     */
    public function onList($listName, callable $handler, $group = null)
    {
        $uid = $group ?: str::uuid();

        $this->listHandlers[$listName][$uid] = $handler;

        return $uid;
    }

    /**
     * @param $listName
     * @param null $group
     */
    public function offList($listName, $group = null)
    {
        if ($group) {
            unset($this->listHandlers[$listName][$group]);
        } else {
            unset($this->listHandlers[$listName]);
        }
    }

    /**
     * @param string $event
     * @param callable $callback
     * @param string $group
     * @return string
     */
    public function on($event, callable $callback, $group = null)
    {
        $uid = $group ?: str::uuid();
        $this->handlers[$event][$uid] = $callback;

        return $uid;
    }

    /**
     * @param string $event
     * @param string $group
     */
    public function off($event, $group)
    {
        unset($this->handlers[$event][$group]);
    }


    /**
     * @param string $group
     */
    public function offGroup(string $group)
    {
        $events = [];

        foreach ($this->handlers as $event => $groups) {
            foreach ($groups as $g => $callback) {
                if ($g === $group) {
                    $events[] = $event;
                    break;
                }
            }
        }

        foreach ($events as $event) $this->off($event, $group);
    }

    /**
     * @param $event
     * @param array ...$args
     * @return mixed
     */
    public function trigger($event, ...$args)
    {
        $onLast = [];
        $result = null;

        foreach ((array) $this->handlers[$event] as $handler) {
            $result = $handler(...$args);

            if (is_callable($result)) {
                $onLast[] = $result;
                continue;
            }

            if ($result) {
                break;
            }
        }

        foreach ($onLast as $handler) {
            $handler(...$args);
        }

        return $result;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    public function hasBehaviour($type)
    {
        return isset($this->behaviours[$type]);
    }

    /**
     * @param string $type
     *
     * @return AbstractProjectBehaviour
     * @throws Exception
     */
    public function getBehaviour($type)
    {
        $behaviour = $this->behaviours[$type];

        if (!$behaviour) {
            throw new Exception('The "' . $type . '" behaviour is not registered');
        }

        return $behaviour;
    }

    public function removeBehaviour(string $type)
    {
        if ($behaviour = $this->behaviours[$type]) {
            unset($this->behaviours[$type]);
            return true;
        }

        return false;
    }

    /**
     * @return ProjectIndexer
     */
    public function getIndexer()
    {
        return $this->indexer;
    }

    /**
     * @return ProjectRefactorManager
     */
    public function getRefactorManager()
    {
        return $this->refactorManager;
    }

    /**
     * @return ProjectTree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return ProjectRunDebugManager
     */
    public function getRunDebugManager(): ProjectRunDebugManager
    {
        return $this->runDebugManager;
    }

    /**
     * Вызывать при создании проекта.
     */
    public function create()
    {
        $this->refreshSupports();

        //FileSystem::open($this->getMainProjectFile());
        $this->trigger(__FUNCTION__);
    }

    /**
     * Вызывать при смене табов.
     */
    public function update()
    {
        $this->trigger(__FUNCTION__);
    }

    /**
     * Вызвать в момент открытия проекта (после загрузки, создания и восстановления).
     */
    public function open()
    {
        Logger::info("Opening project ...");

        FileSystem::setMenuForAddTab(new ContextMenu());

        if ($this->template) {
            $this->template->openProject($this);
        }

        $this->refreshSupports();

        $this->trigger(__FUNCTION__);

        //if (!$this->indexer->isValid()) { todo implement it
        $this->reindex();
        //  }

        foreach ($this->config->getOpenedFiles() as $file) {
            if ($this->getFile($file)->exists()) {
                $file = $this->getFile($file);
            } else {
                $file = $this->getAbsoluteFile($file);
            }

            if (File::of($file)->exists()) {
                //UXApplication::runLater(function () use ($file) {
                    FileSystem::open($file, false);
                //});
            }
        }

        $selected = $this->config->getSelectedFile();

        if ($this->getFile($selected)->exists()) {
            $selected = $this->getFile($selected);
        }

        if ($selected && File::of($selected)->exists()) {
            uiLater(function () use ($selected) {
                FileSystem::open($selected, true);
            });
        }

        $this->doTick();

        $this->tickTimer->start();

        $this->supportRefreshTimer = Timer::every('2.5s', function () {
            if (!Ide::get()->isIdle()) {
                $this->refreshSupports();
            }
        });
    }

    /**
     * Refresh supports.
     */
    public function refreshSupports()
    {
        $time = Time::millis();
        //Logger::info("Refresh project supports ...");

        $ide = Ide::get();

        $supports = $this->supports;

        foreach ($supports as $key => $support) {
            if (!$support->isFit($this)) {
                Logger::info("Unlink support '$key' from project");
                $this->trigger('unlinkSupport', $support);
                $support->onUnlink($this);
                unset($this->supports[$key]);
            }
        }

        $projectSupports = $ide->getProjectSupports();
        $depth = 12;

        while ($projectSupports) {
            foreach ($projectSupports as $key => $support) {
                if ($support) {
                    if (!isset($this->supports[$support->getCode()])) {
                        if ($support->isFit($this)) {
                            Logger::info("Link support '{$support->getCode()}' to project");
                            $support->onLink($this);
                            $this->supports[$support->getCode()] = $support;
                            $projectSupports[$key] = null;
                            $this->trigger('linkSupport', $support);
                        } else {
                            $fitRequiredSupports = $support->getFitRequiredSupports();

                            if ($fitRequiredSupports) {
                                // skip, re-try isFit
                            } else {
                                $projectSupports[$key] = null;
                            }
                            //Logger::debug("Support '{$support->getCode()} is not fitted for the project'");
                        }
                    }
                }
            }

            $depth--;
            if ($depth == 0) {
                break;
            }

            $projectSupports = \flow($projectSupports)->find(fn($el) => $el != null)->toArray();
        }

        $time = Time::millis() - $time;

        if ($time > 1000) {
            Logger::info("Refreshing project supports is done, time = {$time}ms.");
        }
    }

    /**
     * @param string $code
     * @return bool
     */
    public function hasSupport(string $code): bool
    {
        return isset($this->supports[$code]);
    }

    /**
     * @param string $code
     * @return AbstractProjectSupport|null
     * @throws Exception
     */
    static public function findSupportOfCurrent(string $code): ?AbstractProjectSupport
    {
        $project = Ide::project();
        if ($project) {
            return $project->findSupport($code);
        } else {
            return null;
        }
    }

    /**
     * @param string $code
     * @return AbstractProjectSupport
     * @throws Exception
     */
    public function findSupport(string $code): ?AbstractProjectSupport
    {
        if ($support = $this->supports[$code]) {
            return $support;
        }

        /*$ide = Ide::get();

        foreach ($ide->getProjectSupports() as $support) {
            if ($code === $support->getCode()) {
                if (!isset($this->supports[$support->getCode()]) && $support->isFit($this)) {
                    Logger::info("Link support '{$support->getCode()}' to project from Project::findSupport()");
                    $support->onLink($this);
                    $this->supports[$support->getCode()] = $support;
                    $this->trigger('linkSupport', $support);

                    return $support;
                }
            }
        }*/

        return null;
    }

    public function whenSupportLinked(string $code, callable $fn, string $tagId): bool
    {
        $support = $this->findSupport($code);
        if ($support) {
            $fn($support);
            return true;
        } else {
            $id = "$tagId#$code";

            $this->on('linkSupport', function (AbstractProjectSupport $support) use ($id, $fn, $code) {
                if ($support->getCode() === $code) {
                    $this->off('linkSupport', $id);
                    $fn($support);
                }
            });

            return false;
        }
    }

    public function whenSupportUnlinked(string $code, callable $fn, string $tagId)
    {
        $support = $this->findSupport($code);
        if (!$support) {
            $fn($support);
            return true;
        } else {
            $id = "$tagId#$code";

            $this->on('unlinkSupport', function (AbstractProjectSupport $support) use ($id, $fn, $code) {
                if ($support->getCode() === $code) {
                    $this->off('unlinkSupport', $id);
                    $fn($support);
                }
            });

            return false;
        }
    }

    /**
     * Переиндексировать весь проект.
     */
    public function reindex()
    {
        $this->indexer->clear();

        $this->trigger(__FUNCTION__, $this->indexer);

        $this->indexer->save();
    }

    /**
     * @return ProjectExporter
     * @throws \php\lang\IllegalArgumentException
     */
    public function makeExporter()
    {
        $exporter = new ProjectExporter($this);
        $exporter->addDirectory($this->getIdeDir());
        $exporter->addFile($this->getProjectFile());

        $gitIgnoreFile = $this->getFile('.gitignore');

        if ($gitIgnoreFile->exists()) {
            $exporter->addFile($gitIgnoreFile);
        }

        $exporter->removeFile($this->indexer->getIndexFile());
        $exporter->removeFile($this->getIdeLibraryConfig());
        $exporter->removeFile($this->getIdeFile("ide.lock"));
        $exporter->removeDirectory($this->getIdeCacheFile(''));

        $this->trigger('export', $exporter);

        return $exporter;
    }

    /**
     * @param $file
     */
    public function export($file)
    {
        Logger::info("Project export to: $file");

        $this->makeExporter()->save($file);
    }

    /**
     * Загрузить данные проекта.
     */
    public function load()
    {
        Logger::info("Project loading ...");

        $this->inspectors = [];
        //$this->inspectorLoaderThreadPoll = ThreadPool::createSingle();

        $dir = $this->getIdeDir();

        if (!$dir->isDirectory()) {
            $dir->mkdirs();
        }

        $this->template    = $this->config->getTemplate();
        $this->packageName = $this->config->getPackageName();

        $this->config->createBehaviours($this);

        if ($this->template) {
            $this->template->recoveryProject($this);
        } else {
            throw new InvalidProjectFormatException("Unable to fetch template project");
        }

        $this->behaviours = arr::sort($this->behaviours, function (AbstractProjectBehaviour $a, AbstractProjectBehaviour $b) {
            if ($a->getPriority() > $b->getPriority()) {
                return 1;
            } else {
                if ($a->getPriority() < $b->getPriority()) {
                    return -1;
                }

                return 0;
            }
        }, true);

        foreach ($this->behaviours as $behaviour) {
            Logger::info("Inject behaviour: " . reflect::typeOf($behaviour));
            $behaviour->inject();
        }

        $this->trigger(__FUNCTION__);
    }

    /**
     * @deprecated
     * @param $name
     */
    public function saveIdeConfig($name)
    {
        $config = $this->ideConfigs[$name];

        if ($config) {
            $config->saveFile();
        }
    }

    /**
     * Сохранить все данные проекта.
     */
    public function save()
    {
        Logger::info("Start project saving ...");

        $this->trigger(__FUNCTION__);

        if ($editor = FileSystem::getSelectedEditor()) {
            $editor->save();
        }

        foreach ($this->ideConfigs as $name => $config) {
            if ($config->isAutoSave()) {
                $config->saveFile();
            }
        }

        $files = Flow::of(FileSystem::getOpened())->map(function ($e) { return $this->getAbsoluteFile($e['file']); })->toArray();
        $windowFiles = [];

        foreach ($files as $file) {
            if (!FileSystem::isTabbed($file)) {
                $windowFiles[] = $file;
            }
        }

        $this->config->setTreeState($this->tree);
        $this->config->setOpenedFiles($files, FileSystem::getSelected(), $windowFiles);
        $this->config->setBehaviours($this->behaviours);

        $this->config->setProject($this);

        $this->config->save();

        Logger::info("Project is saved.");
    }

    /**
     * Восстановить целостность файлов проекта.
     */
    public function recover()
    {
        $this->trigger(__FUNCTION__);
    }

    /**
     * @param $environment
     * @param callable|null $log
     */
    public function preCompile($environment, callable $log = null)
    {
        Logger::info("Precompile project: env = $environment");

        $this->trigger(__FUNCTION__, $environment, $log);
    }

    /**
     * @param string $environment dev, prod, test, etc.
     * @param callable $log
     */
    public function compile($environment, callable $log = null)
    {
        Logger::info("Compile project: env = $environment");

        $this->trigger(__FUNCTION__, $environment, $log);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isIgnoredPath($path)
    {
        foreach ($this->ignoreRules as $rule) {
            if (File::of($path)->matches($rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $any
     *
     * @param bool $inject
     * @return AbstractProjectBehaviour
     */
    public function register($any, $inject = true)
    {
        if ($any instanceof AbstractProjectBehaviour) {
            return $this->behaviours[get_class($any)] = $any->forProject($this, $inject);
        } else {
            throw new \InvalidArgumentException("Unable to register an instance of class " . get_class($any));
        }
    }

    /**
     * @param $file
     *
     * @return ProjectFile
     */
    protected function fetchFile($file)
    {
        return new ProjectFile($this, $file);
    }

    /**
     * @param AbstractProjectTemplate $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @param string $packageName
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * @return AbstractProjectTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function close($save = true)
    {
        Logger::info("Close project ...");
        $this->trigger(__FUNCTION__);

        if ($this->supportRefreshTimer) {
            $this->supportRefreshTimer->cancel();
            $this->supportRefreshTimer = null;
        }

        FileSystem::setClickOnAddTab(null);

        $this->tickTimer->stop();

        $file = $this->getIdeFile("ide.lock");
        $file->delete();
        $file->deleteOnExit();

        if ($save) {
            $this->save();
        }

        foreach ($this->supports as $support) {
            $support->onUnlink($this);
        }

        $this->ideConfigs = [];

        foreach ($this->inspectors as $inspector) {
            $inspector->free();
        }

        $this->inspectors = [];

        $this->inspectorLoaderThreadPoll->shutdown();

        FileSystem::setMenuForAddTab(null);
    }

    function free()
    {
        foreach ($this->inspectors as $one) {
            $one->free();
        }

        $this->inspectors = [];
        $this->configConfigurers = [];
        $this->indexer = null;
        $this->config = null;
        $this->handlers = [];

        $this->ignoreRules = [];
        $this->refactorManager = null;
        $this->tree = null;

        $this->tickTimer->free();
        $this->tickTimer = null;

        $this->behaviours = [];
    }

    /**
     * @param string $fileName
     * @return ProjectFile[]
     */
    public function findDuplicatedFiles($fileName)
    {
        $file = File::of($fileName);

        $length = $file->length();
        $crc  = $file->crc32();
        $hash = $file->hash('SHA-256');

        $duplicates = [];

        FileUtils::scan($this->getFile('src/'), function ($filename) use ($crc, $length, $hash, &$duplicates) {
            $file = File::of($filename);

            if (!$file->isFile()) {
                return;
            }

            if ($file->length() !== $length) {
                return;
            }

            if ($file->crc32() !== $crc) {
                return;
            }

            if ($file->hash('SHA-256') !== $hash) {
                return;
            }

            $duplicates[] = $this->getAbsoluteFile($filename);
        });

        return $duplicates;
    }

    /**
     * @param $fileName
     * @param $directory
     * @return ProjectFile
     */
    public function copyFile($fileName, $directory)
    {
        $file = File::of($fileName);
        $name = $file->getName();

        $directory = $this->getFile($directory);

        $x = 2;

        while (fs::exists($directory . '/' . $name)) {
            $name = fs::pathNoExt($file->getName()) . ($x++) . '.' . fs::ext($file->getName());
        }

        $newFile = "$directory/$name";

        FileUtils::copyFile($fileName, $newFile);

        return $this->getAbsoluteFile($newFile);
    }

    public function isOpenedInOtherIde()
    {
        $lockFile = $this->getIdeFile("ide.lock");

        if ($lockFile->exists()) {
            $pid = FileUtils::get($lockFile);

            if ($pid) {
                if ($pid > Time::millis() - 15 * 1000) {
                    return true;
                }
            }
        }

        return false;
    }

    public function loadSourceForInspector($path)
    {
        $inspectors = $this->inspectors;

        Logger::debug("Load source for inspector: $path");

        if (!$inspectors) {
            Logger::warn("Unable to loadSourceForInspector(), inspectors are empty.");
            return;
        }

        if (!$this->inspectorLoaderThreadPoll->isShutdown()) {
            $this->inspectorLoaderThreadPoll->execute(function () use ($path, $inspectors) {
                foreach ($inspectors as $one) {
                    if (!$one->loadSourceWithCache($path)) {
                        Logger::warn("Unable to load source for inspector, $path");
                    }
                }
            });
        }
    }

    public function unloadSourceForInspector($path)
    {
        $inspectors = $this->inspectors;

        if (!$inspectors) {
            Logger::warn("Unable to unloadSourceForInspector(), inspectors are empty.");
        }

        if (!$this->inspectorLoaderThreadPoll->isShutdown()) {
            $this->inspectorLoaderThreadPoll->execute(function () use ($path, $inspectors) {
                foreach ($inspectors as $one) {
                    $one->unloadSource($path);
                }
            });
        }
    }

    public function loadDirectoryForInspector($path, array $options = [], callable $done = null)
    {
        $inspectors = $this->inspectors;

        Logger::debug("Load directory for inspector: $path");

        if (!$inspectors) {
            Logger::warn("Unable to loadDirectoryForInspector(), inspectors are empty.");
        }

        if (!$this->inspectorLoaderThreadPoll->isShutdown()) {
            $this->inspectorLoaderThreadPoll->execute(function () use ($path, $inspectors, $options, $done) {
                foreach ($inspectors as $one) {
                    $one->loadDirectory($path, $options);
                }

                if ($done) {
                    $done();
                }
            });
        }
    }

    public function unloadDirectoryForInspector($path): Promise
    {
        Logger::debug("Unload directory for inspector: $path");

        $inspectors = $this->inspectors;

        if (!$inspectors) {
            Logger::warn("Unable to unloadDirectoryForInspector(), inspectors are empty.");
        }

        if (!$this->inspectorLoaderThreadPoll->isShutdown()) {
            return new Promise(function ($resolve, $reject) use ($path, $inspectors) {
                $this->inspectorLoaderThreadPoll->execute(function () use ($path, $inspectors, $resolve, $reject) {
                    try {
                        foreach ($inspectors as $one) {
                            $one->unloadDirectory($path);
                        }

                        $resolve(true);
                    } catch (Throwable $e) {
                        $reject($e);
                    }
                });
            });
        } else {
            return Promise::reject(new Exception("Unable to unloadDirectoryForInspector(), inspectors are empty."));
        }
    }

    /**
     * @return AbstractInspector[]
     */
    public function getInspectors()
    {
        return $this->inspectors;
    }

    /**
     * @param $context
     * @return AbstractInspector
     */
    public function getInspector($context)
    {
        return $this->inspectors[$context];
    }

    /**
     * @param string $context
     * @param AbstractInspector $inspector
     */
    public function registerInspector($context, AbstractInspector $inspector)
    {
        $this->trigger('registerInspector', $context, $inspector);
        $this->inspectors[str::lower($context)] = $inspector;
    }

    /**
     * @param string $context
     */
    public function unregisterInspector($context)
    {
        $this->trigger('unregisterInspector', $context);
        unset($this->inspectors[str::lower($context)]);
    }
}