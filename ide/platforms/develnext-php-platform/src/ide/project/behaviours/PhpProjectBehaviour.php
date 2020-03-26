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

        $javafx = $this->project->findSupport('javafx');

        if ($javafx) {
            $useByteCode = Project::ENV_PROD == $env;

            $dirs = [];

            if ($bundle = BundleProjectBehaviour::get()) {
                foreach ($bundle->fetchAllBundles($env) as $one) {
                    $dirs[] = $one->getProjectVendorDirectory() . '/.inc';
                }
            }

            $javafx->saveBootstrapScript($this->project, $dirs, $useByteCode && $this->isByteCodeEnabled());
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