<?php
namespace ide\project\behaviours;

use Error;
use develnext\lexer\inspector\PHPInspector;
use ide\Logger;
use ide\formats\PhpCodeFormat;
use ide\project\AbstractProjectBehaviour;
use ide\project\Project;
use ide\project\ProjectFile;
use ide\project\behaviours\php\TreeCreatePhpClassMenuCommand;
use ide\project\behaviours\php\TreeCreatePhpFileMenuCommand;
use ide\project\control\CommonProjectControlPane;
use ide\utils\FileUtils;
use php\compress\ZipFile;
use php\framework\FrameworkPackageLoader;
use php\gui\UXCheckbox;
use php\gui\UXComboBox;
use php\gui\UXLabel;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\io\File;
use php\io\FileStream;
use php\io\IOException;
use php\io\Stream;
use php\lang\Environment;
use php\lang\Module;
use php\lang\Package;
use php\lang\Thread;
use php\lang\ThreadPool;
use php\lib\arr;
use php\lib\fs;
use php\lib\str;
use php\net\URL;
use php\util\Flow;

/**
 * Class PhpProjectBehaviour
 * @package ide\project\behaviours
 */
class PhpProjectBehaviour extends AbstractProjectBehaviour
{
    const OPT_COMPILE_BYTE_CODE = 'compileByteCode';
    const OPT_IMPORT_TYPE_CODE = 'importType';

    const GENERATED_DIRECTORY = 'src_generated';

    private static $importTypes = [
        'simple' => 'php.use.type.simple.option::Имена классов (use namespace\\ClassName)',
        'package' => 'php.use.type.package.option::Имена пакетов (use package)'
    ];

    /**
     * @var array
     */
    protected $globalUseImports = [];

    /**
     * @var UXVBox
     */
    protected $uiSettings;

    /**
     * @var UXCheckbox
     */
    protected $uiByteCodeCheckbox;

    /**
     * @var PHPInspector
     */
    protected $inspector;

    /**
     * @var Package
     */
    protected $projectPackage;

    /**
     * @var ThreadPool
     */
    protected $inspectorThreadPool;

    /**
     * @var UXComboBox
     */
    protected $uiImportTypesSelect;

    /**
     * @return int
     */
    public function getPriority()
    {
        return self::PRIORITY_CORE;
    }

    /**
     * @return PHPInspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    /**
     * ...
     */
    public function inject()
    {
        $this->project->on('save', [$this, 'doSave']);
        $this->project->on('preCompile', [$this, 'doPreCompile']);
        $this->project->on('compile', [$this, 'doCompile']);

        $this->project->on('makeSettings', [$this, 'doMakeSettings']);
        $this->project->on('updateSettings', [$this, 'doUpdateSettings']);

        $this->project->registerFormat(new PhpCodeFormat());

        $this->registerTreeMenu();
    }

    /**
     * Получить метод импортирования классов
     * @return  string simple|package
     */
    public function getImportType(): string
    {   
        $default = 'simple';
        $type = $this->getIdeConfigValue(self::OPT_IMPORT_TYPE_CODE, $default);
        if(!isset(static::$importTypes[$type])){
            $this->setImportType($default);
            return $default;
        }

        return $type;
    }

    public function setImportType($value)
    {
        $this->setIdeConfigValue(self::OPT_IMPORT_TYPE_CODE, $value);
    }

    protected function getProjectPackage()
    {
        $package = ['classes' => [], 'functions' => [], 'constants' => []];

        $dirs = [];

        if ($this->project->getSrcDirectory() !== null) {
            $dirs[] = $this->project->getSrcFile('');
        }

        if ($this->project->getSrcGeneratedDirectory() !== null) {
            $dirs[] = $this->project->getSrcFile('', true);
        }

        foreach ($dirs as $directory) {
            fs::scan($directory, function ($filename) use ($directory, &$package) {
                if (fs::ext($filename) == 'php') {
                    $classname = FileUtils::relativePath($directory, $filename);

                    if ($classname[0] == '.') {
                        return;
                    }

                    $classname = fs::pathNoExt($classname);
                    $classname = str::replace($classname, '/', '\\');
                    $package['classes'][] = $classname;
                }
            });
        }

        return $package;
    }

    protected function registerTreeMenu()
    {
        $menu = $this->project->getTree()->getContextMenu();
        $menu->add(new TreeCreatePhpFileMenuCommand($this->project->getTree()), 'new');
        $menu->add(new TreeCreatePhpClassMenuCommand($this->project->getTree()), 'new');
    }

    public function doClose()
    {
        //$this->inspectorThreadPool->shutdown();
        //$this->inspector->free();

        $this->uiSettings = null;
        $this->uiByteCodeCheckbox = null;
        $this->globalUseImports = null;
        $this->uiImportTypesSelect = null;
    }

    public function doSave()
    {
        if ($this->uiSettings) {
            $this->setIdeConfigValue(self::OPT_COMPILE_BYTE_CODE, $this->uiByteCodeCheckbox->selected);
            $this->setImportType(arr::keys(static::$importTypes)[$this->uiImportTypesSelect->selectedIndex]);
        }
    }

    public function doPreCompile($env, callable $log = null)
    {
        $result = fs::clean("{$this->project->getRootDir()}/{$this->project->getSrcGeneratedDirectory()}");

        if ($result['error']) {
            foreach ($result['error'] as $file) {
                Logger::error("Failed to delete file: $file");
            }
        }

        $directories = [$this->project->getSrcFile(""), $this->project->getSrcFile("", true)];

        $cacheIgnore = [];

        foreach ($directories as $directory) {
            fs::scan($directory, function ($filename) use ($directory, $log, &$cacheIgnore) {
                $name = FileUtils::relativePath($directory, $filename);

                if (fs::ext($name) == 'php') {
                    $cacheIgnore[] = $name;

                    $file = 'bytecode/' . fs::pathNoExt($name) . '.phb';

                    $this->project->clearIdeCache($file);
                }
            });
        }

        FileUtils::put($this->project->getIdeCacheFile('bytecode/.cacheignore'), str::join($cacheIgnore, "\n"));

        fs::scan($this->project->getSrcFile(''), function ($filename) {
            if (fs::ext($filename) == 'phb') {
                fs::delete($filename);
            }
        });

        if ($this->inspector) {
            $packageName = $this->project->getPackageName();

            $file = $this->project->getSrcFile("JPHP-INF/packages/$packageName", true);
            fs::ensureParent($file);
            fs::delete($file);

            $fs = new FileStream($file, 'w+');

            $package = $this->getProjectPackage();

            try {
                $fs->write("[classes]\n");

                foreach ((array)$package['classes'] as $type) {
                    if ($type) {
                        $fs->write($type . "\n");
                    }
                }
            } finally {
                $fs->close();
            }
        }

        if ($gui = GuiFrameworkProjectBehaviour::get()) {
            $useByteCode = Project::ENV_PROD == $env;

            $dirs = [];

            if ($bundle = BundleProjectBehaviour::get()) {
                foreach ($bundle->fetchAllBundles($env) as $one) {
                    $dirs[] = $one->getProjectVendorDirectory() . '/.inc';
                }
            }

            $gui->saveBootstrapScript($dirs, $useByteCode && $this->isByteCodeEnabled());
        }
    }

    public function isByteCodeEnabled() {
        return $this->getIdeConfigValue(self::OPT_COMPILE_BYTE_CODE, true);
    }

    public function setByteCodeEnabled($value) {
        return $this->setIdeConfigValue(self::OPT_COMPILE_BYTE_CODE, $value);
    }

    protected function collectZipLibraries()
    {
        $result = [];

        foreach ($this->project->getModules() as $module) {
            switch ($module->getType()) {
                case 'zipfile':
                case 'jarfile':
                    if (!$module->isProvided()) {
                        $result[] = fs::abs($module->getId());
                    }

                    break;
            }
        }

        return $result;
    }

    public function doCompile($env, callable $log = null)
    {
        $useByteCode = Project::ENV_PROD == $env;

        if ($useByteCode && $this->isByteCodeEnabled()) {
            $scope = new Environment(null, Environment::HOT_RELOAD);
            $scope->importClass(FileUtils::class);

            $zipLibraries = $this->collectZipLibraries();

            $generatedDirectory = $this->project->getSrcFile('', true);
            $dirs = [$generatedDirectory, $this->project->getSrcFile('')];

            $includedFiles = [];

            if ($bundle = BundleProjectBehaviour::get()) {
                foreach ($bundle->fetchAllBundles($env) as $one) {
                    $dirs[] = $one->getProjectVendorDirectory();
                }
            }

            // Add packages -------------------------------
            foreach ($dirs as $dir) {
                fs::scan("$dir/.packages", function ($filename) use ($scope) {
                    $ext = fs::ext($filename);

                    if ($ext == 'pkg') {
                        $package = FrameworkPackageLoader::makeFrom($filename);
                        $scope->setPackage(fs::nameNoExt($filename), $package);
                    }
                }, 1);
            }

            foreach ($zipLibraries as $library) {
                $zip = new ZipFile($library);
                foreach ($zip->statAll() as $stat) {
                    $name = $stat['name'];

                    if (str::startsWith($name, '.packages/') && fs::ext($name) == 'pkg') {
                        $zip->read($stat['name'], function (array $stat, Stream $stream) use ($name, $scope) {
                            $package = FrameworkPackageLoader::makeFrom($stream);
                            $scope->setPackage(fs::nameNoExt($name), $package);
                        });
                    }
                }
            }
            // ----------------------------------------------

            $scope->execute(function () use ($zipLibraries, $generatedDirectory, $dirs, &$includedFiles) {
                ob_implicit_flush(true);

                spl_autoload_register(function ($name) use ($zipLibraries, $generatedDirectory, $dirs, &$includedFiles) {
                    echo("Try class '$name' auto load\n");

                    foreach ($dirs as $dir) {
                        $filename = "$dir/$name.php";

                        if (fs::exists($filename)) {
                            echo "Find class '$name' in ", $filename, "\n";

                            $compiled = new File($generatedDirectory, $name . ".phb");
                            fs::ensureParent($compiled);

                            $includedFiles[FileUtils::hashName($filename)] = true;

                            $fileStream = new FileStream($filename);
                            $module = new Module($fileStream, false, true);
                            $module->dump($compiled, true);
                            $fileStream->close();
                            return;
                        }
                    }
                    foreach ($zipLibraries as $file) {
                        if (!fs::exists($file)) {
                            echo "SKIP $file, is not exists.\n";
                            continue;
                        }

                        try {
                            $name = str::replace($name, '\\', '/');

                            $url = new URL("jar:file:///$file!/$name.php");

                            $conn = $url->openConnection();
                            $stream = $conn->getInputStream();

                            $module = new Module($stream, false);
                            $module->call();

                            $stream->close();

                            echo "Find class '$name' in ", $file, "\n";

                            $compiled = new File($generatedDirectory, $name . ".phb");

                            fs::ensureParent($compiled);

                            $module->dump($compiled, true);

                            return;
                        } catch (IOException $e) {
                            echo "[ERROR] {$e->getMessage()}\n";
                            // nop.
                        }
                    }
                });
            });

            foreach ($dirs as $i => $dir) {
                fs::scan($dir, function ($filename) use ($log, $scope, $i, $useByteCode, $generatedDirectory, $dir, &$includedFiles) {
                    $relativePath = FileUtils::relativePath($dir, $filename);

                    if ($i == 1) { // ignore src files if they exist in src_generated dir.
                        if (fs::exists($this->project->getSrcFile($relativePath, true))) {
                            return;
                        }
                    }

                    if (str::endsWith($filename, '.php')) {
                        if ($includedFiles[FileUtils::hashName($filename)]) {
                            return;
                        }

                        $filename = fs::normalize($filename);

                        if ($log) {
                            $log(":compile $filename");
                        }

                        $compiledFile = new File($generatedDirectory, '/' . fs::pathNoExt($relativePath) . '.phb');

                        if ($compiledFile->getParentFile() && !$compiledFile->getParentFile()->isDirectory()) {
                            $compiledFile->getParentFile()->mkdirs();
                        }

                        $includedFiles[FileUtils::hashName($filename)] = true;
                        $scope->execute(function () use ($filename, $compiledFile) {
                            $fileStream = new FileStream($filename);
                            $module = new Module($fileStream, false, true);
                            $stream = new FileStream($compiledFile, 'w+');
                            $module->dump($stream, true);
                            $stream->close();
                            $fileStream->close();
                        });
                    }
                });
            }

            fs::scan($generatedDirectory, function ($filename) use ($log, $scope, $useByteCode, &$includedFiles) {
                if (fs::ext($filename) == 'php') {
                    if ($includedFiles[FileUtils::hashName($filename)]) {
                        return;
                    }

                    $filename = fs::normalize($filename);

                    if ($log) $log(":compile-gen $filename");

                    $compiledFile = fs::pathNoExt($filename) . '.phb';

                    $includedFiles[FileUtils::hashName($filename)] = true;

                    $scope->execute(function () use ($filename, $compiledFile) {
                        $stream = new FileStream($compiledFile, 'w+');
                        $fileStream = new FileStream($filename);
                        $module = new Module($fileStream, false, true);
                        $module->dump($stream);
                        $stream->close();
                        $fileStream->close();
                    });

                    if (!fs::delete($filename)) {
                        $log("[WARNING]: Failed to delete file $filename");
                    }
                }
            });

            foreach ($zipLibraries as $library) {
                if (!fs::exists($library)) {
                    continue;
                }

                $jar = new ZipFile($library);

                foreach ($jar->statAll() as $stat) {
                    list($name) = [$stat['name']];

                    if (str::startsWith($name, 'JPHP-INF/')) {
                        continue;
                    }

                    if (fs::ext($name) == 'php') {
                        $compiled = new File($generatedDirectory, '/' . fs::pathNoExt($name) . ".phb");

                        if (!$compiled->exists()) {
                            if ($compiled->getParentFile() && !$compiled->getParentFile()->isDirectory()) {
                                $compiled->getParentFile()->mkdirs();
                            }

                            $jar->read($name, function ($_, Stream $stream) use ($name, $compiled, $log, $scope) {
                                $className = fs::pathNoExt($name);
                                $className = str::replace($className, '/', '\\');

                                try {
                                    $done = $scope->execute(function () use ($stream, $compiled, $className, $log) {
                                        if (!class_exists($className, false)) {
                                            try {
                                                $module = new Module($stream, false);
                                                $module->dump($compiled, true);
                                                return true;
                                            } catch (Error $e) {
                                                if ($log) {
                                                    $log("[ERROR] Unable to compile '{$className}', {$e->getMessage()}, on line {$e->getLine()}");
                                                    return false;
                                                }
                                            }
                                        }

                                        return false;
                                    });

                                    if ($log && $done) {
                                        $log(":compile {$name}");
                                    }
                                } finally {
                                    $stream->close();
                                }
                            });
                        }
                    }
                }
            }
        }
    }

    public function doUpdateSettings(CommonProjectControlPane $editor = null)
    {
        if ($this->uiSettings) {
            $this->uiByteCodeCheckbox->selected = $this->getIdeConfigValue(self::OPT_COMPILE_BYTE_CODE, true);
            $this->uiImportTypesSelect->selectedIndex = Flow::of(arr::keys(static::$importTypes))->findValue($this->getImportType());
        }
    }

    public function doMakeSettings(CommonProjectControlPane $editor)
    {
        $title = _(new UXLabel('php.source.code::Исходный php код:'));
        $title->font = $title->font->withBold();

        $opts = new UXHBox();
        $opts->spacing = 10;
        $opts->alignment = 'BOTTOM_LEFT';

        $this->uiByteCodeCheckbox = $byteCodeCheckbox = new UXCheckbox('php.option.compile.to.bytecode::Компилировать в байткод (+ защита от декомпиляции)');
        $byteCodeCheckbox->padding = 5;
        $this->uiByteCodeCheckbox->on('mouseUp', [$this, 'doSave']);
        $byteCodeCheckbox->tooltipText = 'php.option.compile.to.bytecode.help::Компиляция будет происходить только во время итоговой сборки проекта.';
        $opts->add(_($byteCodeCheckbox));

        $importTitle = _(new UXLabel('php.option.use.type.for.classes::Метод импортирования классов:'));
        $importTypeSelect = new UXComboBox(static::$importTypes);

        $importTypeSelect->on('action', function () {
            $this->setImportType(arr::keys(static::$importTypes)[$this->uiImportTypesSelect->selectedIndex]);
        });

        $this->uiImportTypesSelect = _($importTypeSelect);

        $importTypeSelect->padding = 5;
        $importTypeSelect->minWidth = 350;
        $opts->children->insert(0, new UXVBox([$importTitle, $importTypeSelect], 5));

        $ui = new UXVBox([$title, $opts]);
        $ui->spacing = 5;
        $this->uiSettings = $ui;

        $editor->addSettingsPane($ui);
    }
}