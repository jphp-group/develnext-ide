<?php
namespace ide\project\supports;

use ide\action\ActionManager;
use ide\behaviour\IdeBehaviourDatabase;
use ide\commands\CreateFormProjectCommand;
use ide\commands\CreateGameSpriteProjectCommand;
use ide\commands\CreateScriptModuleProjectCommand;
use ide\editors\AbstractEditor;
use ide\editors\common\ObjectListEditorItem;
use ide\editors\FormEditor;
use ide\editors\menu\ContextMenu;
use ide\editors\ScriptModuleEditor;
use ide\entity\ProjectSkin;
use ide\formats\form\AbstractFormElement;
use ide\formats\FxCssCodeFormat;
use ide\formats\GameSpriteFormat;
use ide\formats\GuiFormFormat;
use ide\formats\ProjectFormat;
use ide\formats\ScriptModuleFormat;
use ide\formats\templates\GuiApplicationConfFileTemplate;
use ide\formats\templates\GuiBootstrapFileTemplate;
use ide\formats\templates\GuiFormFileTemplate;
use ide\formats\templates\GuiLauncherConfFileTemplate;
use ide\formats\templates\PhpClassFileTemplate;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\IdeException;
use ide\library\IdeLibrarySkinResource;
use ide\Logger;
use ide\project\AbstractProjectSupport;
use ide\project\behaviours\gui\SkinManagerForm;
use ide\project\behaviours\GuiFrameworkProjectBehaviour_ProjectTreeMenuCommand;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\control\CommonProjectControlPane;
use ide\project\control\DesignProjectControlPane;
use ide\project\control\FormsProjectControlPane;
use ide\project\control\ModulesProjectControlPane;
use ide\project\Project;
use ide\project\ProjectExporter;
use ide\project\ProjectFile;
use ide\project\ProjectIndexer;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use ide\utils\Json;
use php\compress\ZipException;
use php\gui\event\UXEvent;
use php\gui\UXApplication;
use php\io\File;
use php\io\IOException;
use php\io\ResourceStream;
use php\lib\fs;
use php\lib\reflect;
use php\lib\str;
use php\util\Configuration;
use timer\AccurateTimer;

/**
 * Class JavaFXProjectSupport
 * @package ide\project\supports
 */
class JavaFXProjectSupport extends AbstractProjectSupport
{
    public function getCode()
    {
        return 'javafx';
    }

    public function getFitRequiredSupports(): array
    {
        return ['jppm'];
    }

    /**
     * @param Project $project
     * @return mixed
     * @throws \Exception
     */
    public function isFit(Project $project)
    {
        /** @var JPPMProjectSupport $jppm */
        if ($project->hasSupport('jppm')) {
            $jppm = $project->findSupport('jppm');
            return $jppm->hasDep('jphp-gui-ext') || $jppm->hasDep('dn-app-framework');
        } else {
            return false;
        }
    }

    public function getAppConfig(Project $project): ?Configuration
    {
        return $project->data(self::class . '#appConfig');
    }

    /**
     * @param Project $project
     * @throws \ide\IdeException
     * @throws \php\lang\IllegalArgumentException
     */
    public function onLink(Project $project)
    {
        $project->on('update', fn() => $this->onProjectUpdate($project), self::class);
        $project->on('export', fn($exporter) => $this->onProjectExport($project, $exporter), self::class);
        $project->on('reindex', fn($indexer) => $this->onProjectReindex($project, $indexer), self::class);
        $project->on('createEditor', fn($editor) => $this->onCreateEditor($project, $editor), self::class);

        $project->data(self::class . '#appConfig', $appConfig = new Configuration());
        try {
            $appConfig->load($project->getFile('src/.system/application.conf'));

            $this->setMainForm($project, $appConfig->get('app.mainForm', ''));
            $this->setAppUuid($project, $appConfig->get('app.uuid', str::uuid()));
        } catch (\Throwable $e) {
            Logger::warn("Unable to load application.conf, {$e->getMessage()}");
        }

        if (!$this->getAppUuid($project)) {
            $this->setAppUuid($project, str::uuid());
        }

        $project->data(self::class . '#ideStyleFile', $project->getIdeCacheFile('.theme/style-ide.css'));

        $ideStylesheetTimer = new AccurateTimer(100, fn() => $this->reloadStylesheetIfModified($project));
        $ideStylesheetTimer->start();
        $project->data(self::class . '#ideStyleTimer', $ideStylesheetTimer);

        $projectFormat = $project->getRegisteredFormat(ProjectFormat::class);

        $project->registerFormat($guiFormFormat = new GuiFormFormat());
        $project->registerFormat(new ScriptModuleFormat());
        $project->registerFormat(new GameSpriteFormat());
        $project->registerFormat(new FxCssCodeFormat());

        $projectFormat->addControlPanes([
            new CommonProjectControlPane(),
            new DesignProjectControlPane(),

            new FormsProjectControlPane(),
            new ModulesProjectControlPane(),
        ]);

        if ($guiFormFormat) {
            $guiFormFormat->registerInternalList('.dn/bundle/uiDesktop/formComponents');
        } else {
            Logger::error("Unable to register components, GuiFormFormat is not found.");
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->registerInternalList('.dn/bundle/uiDesktop/behaviours');
        }

        $project->whenSupportLinked(
            'visprog',
            fn(AbstractProjectSupport $visprog) => $visprog->registerActions($project, '.dn/bundle/uiDesktop/actionTypes'), self::class
        );

        $this->registerTreeMenu($project);

        $this->onProjectOpen($project);
    }

    /**
     * @param Project $project
     * @throws \ide\IdeException
     */
    public function onUnlink(Project $project)
    {
        $project->whenSupportUnlinked(
            'visprog',
            fn(AbstractProjectSupport $visprog) => $visprog->unregisterActions($project, '.dn/bundle/uiDesktop/actionTypes'), self::class
        );

        $project->offGroup(self::class);
        FileSystem::setClickOnAddTab(null);
        $this->unregisterTreeMenu($project);

        $format = Ide::get()->getRegisteredFormat(GuiFormFormat::class);

        if ($format) {
            $format->unregisterInternalList('.dn/bundle/uiDesktop/formComponents');
        }

        if ($bDatabase = IdeBehaviourDatabase::get()) {
            $bDatabase->unregisterInternalList('.dn/bundle/uiDesktop/behaviours');
        }

        $project->unregisterFormat(FxCssCodeFormat::class);
        $project->unregisterFormat(GameSpriteFormat::class);
        $project->unregisterFormat(ScriptModuleFormat::class);
        $project->unregisterFormat(GuiFormFormat::class);

        /** @var AccurateTimer $ideStylesheetTimer */
        $ideStylesheetTimer = $project->data(self::class . '#ideStyleTimer');
        if ($ideStylesheetTimer) {
            $ideStylesheetTimer->stop();
        }

        $project->clearData(self::class);
    }

    protected function registerTreeMenu(Project $project)
    {
        $tree = $project->getTree();
        $menu = $tree->getContextMenu();

        $createFormProjectCommand = new CreateFormProjectCommand();
        $createScriptModuleProjectCommand = new CreateScriptModuleProjectCommand();

        $projectTreeNewMenuItems[] = $menu->addSeparator('new');

        $projectTreeNewMenuItems[] = $menu->add($createFormProjectCommand, 'new');
        $projectTreeNewMenuItems[] = $menu->add($createScriptModuleProjectCommand, 'new');

        $project->data(self::class . "#treeNewMenuItems", $projectTreeNewMenuItems);

        $tree->addIgnoreExtensions([
            'behaviour', 'module', 'fxml'
        ]);

        $tree->addIgnorePaths([
            'application.pid',
            'src/.forms', 'src/.scripts', 'src/.system', 'src/.debug', 'src/JPHP-INF'
        ]);

        $tree->addIgnorePaths([
            "{$project->getSrcDirectory()}/.theme/skin"
        ]);

        $tree->addIgnoreFilter(function ($file) {
            if (fs::ext($file) == 'conf') {
                if (fs::isFile(fs::pathNoExt($file) . '.fxml')) {
                    return true;
                }
            }

            return false;
        }, self::class);

        $addMenu = FileSystem::getMenuForAddTab();

        if ($addMenu) {
            $added = [];

            $added[] = $addMenu->add($createFormProjectCommand);
            $added[] = $addMenu->add($createScriptModuleProjectCommand);

            $project->data(self::class . '#tabAddMenu', $added);
        }
    }

    protected function unregisterTreeMenu(Project $project)
    {
        $tree = $project->getTree();
        $menu = $tree->getContextMenu();

        $projectTreeNewMenuItems = $project->data(self::class . "#treeNewMenuItems");
        if ($projectTreeNewMenuItems) {
            $menu->remove($projectTreeNewMenuItems, 'new');
            $project->data(self::class . "#treeNewMenuItems", null);
        }

        $tree->removeIgnoreExtensions([
            'behaviour', 'module', 'fxml'
        ]);

        $tree->removeIgnorePaths([
            'application.pid',
            'src/.forms', 'src/.scripts', 'src/.system', 'src/.debug', 'src/JPHP-INF'
        ]);

        $tree->removeIgnorePaths([
            "{$project->getSrcDirectory()}/.theme/skin"
        ]);

        $tree->removeIgnoreFilter(self::class);

        $addMenu = FileSystem::getMenuForAddTab();

        if ($addMenu) {
            $tabAddMenu = $project->data(self::class . "#tabAddMenu");
            if ($tabAddMenu) {
                $menu->remove($tabAddMenu);
                $project->data(self::class . "#tabAddMenu", null);
            }
        }
    }

    public function onProjectOpen(Project $project)
    {
        // Set config for prototype forms.
        foreach ($this->getFormEditors($project) as $editor) {
            $usagePrototypes = $editor->getPrototypeUsageList();

            foreach ($usagePrototypes as $factoryId => $ids) {
                $formEditor = $this->getFormEditor($project, $factoryId);

                if (!$formEditor) {
                    Logger::warn("Cannot find form editor for factory '$factoryId'.");
                    continue;
                }

                if ($formEditor && !$formEditor->getConfig()->get('form.withPrototypes')) {
                    $formEditor->getConfig()->set('form.withPrototypes', true);
                    $formEditor->saveConfig();
                }
            }
        }

        $this->loadLauncherConfig($project);
        $this->reloadStylesheetIfModified($project);
    }

    public function onProjectReindex(Project $project, ProjectIndexer $indexer)
    {
        foreach ($this->getFormEditors($project) as $editor) {
            $editor->reindex();
        }

        foreach ($this->getModuleEditors($project) as $editor) {
            $editor->reindex();
        }
    }

    public function onProjectUpdate(Project $project)
    {
        $this->saveBootstrapScript($project);
    }

    public function onProjectExport(Project $project, ProjectExporter $exporter)
    {
        $exporter->addDirectory($project->getFile('src/'));
        $exporter->removeFile($project->getFile('src/.debug'));
    }


    public function onCreateEditor(Project $project, AbstractEditor $editor)
    {
        if (reflect::typeOf($editor) === FormEditor::class) {
            $this->applyStylesheetToEditor($project, $editor);
        }
    }

    /**
     * @return string
     */
    public function getAppUuid(Project $project): ?string
    {
        return $project->getConfig()->getProperty('appUuid');
    }

    /**
     * @param Project $project
     * @param string $appUuid
     * @param bool $trigger
     */
    public function setAppUuid(Project $project, $appUuid, $triggered = true): void
    {
        $project->getConfig()->setProperty('appUuid', $appUuid);
        $this->makeApplicationConf($project);

        if ($triggered) {
            $project->trigger('updateSettings');
        }
    }

    /**
     * @return array
     */
    public function getSplashData(Project $project): array
    {
        return (array) $project->data(self::class . "#splashData");
    }

    /**
     * @param Project $project
     * @return string|null
     */
    public function getMainForm(Project $project): ?string
    {
        return $project->data(self::class . "#mainForm");
    }

    public function setMainForm(Project $project, string $form)
    {
        $project->data(self::class . "#mainForm", $form);
        $this->makeApplicationConf($project);
    }

    public function isMainForm(Project $project, FormEditor $editor)
    {
        return $this->getMainForm($project) == $editor->getTitle();
    }

    public function makeApplicationConf(Project $project)
    {
        $project->createFile('src/.system/application.conf', new GuiApplicationConfFileTemplate($project, $this));
    }

    /**
     * TODO jppm app plugin
     * @param Project $project
     */
    public function saveLauncherConfig(Project $project)
    {
        /*$template = new GuiLauncherConfFileTemplate();
        $template->setFxSplashAlwaysOnTop($this->splashData['alwaysOnTop']);

        if ($this->splashData['src']) {
            $template->setFxSplash($this->splashData['src']);
        }

        //$this->project->defineFile($this->project->getSrcDirectory() . '/JPHP-INF/launcher.conf', $template, true);
        $this->makeApplicationConf();*/
    }


    /**
     * TODO jppm app plugin
     * @param Project $project
     */
    public function loadLauncherConfig(Project $project)
    {
        /*$config = new Configuration();

        $file = $project->getSrcFile('JPHP-INF/launcher.conf');

        if ($file->isFile()) {
            try {
                $config->load($file);

                $this->splashData['src'] = $config->get('fx.splash');
                $this->splashData['alwaysOnTop'] = $config->getBoolean('fx.splash.alwaysOnTop');
                $this->splashData['autoHide'] = $this->applicationConfig->getBoolean('app.fx.splash.autoHide', true);
            } catch (IOException $e) {
                Logger::warn("Unable to load {$file}, {$e->getMessage()}");
            }
        }*/
    }


    public function saveBootstrapScript(Project $project, array $dirs = [], $encoded = false)
    {
        Logger::debug("Save bootstrap script ...");

        $template = new GuiBootstrapFileTemplate();

        $code = "";

        if ($project->getSrcGeneratedDirectory()) {
            $dirs[] = $project->getSrcFile('.inc', true);
        }

        if ($project->getSrcDirectory()) {
            $dirs[] = $project->getSrcFile('.inc', false);
        }

        $incFiles = [];

        foreach ($dirs as $dir) {
            fs::scan($dir, function ($filename) use (&$code, $dir, $encoded, &$incFiles, $project) {
                $ext = fs::ext($filename);

                if (in_array($ext, ['php', 'phb'])) {
                    $file = $project->getAbsoluteFile($filename);

                    if ($encoded && $ext == 'php') {
                        $file = fs::pathNoExt($file) . '.phb';
                    }

                    $incFile = FileUtils::relativePath($dir, $file);

                    if (!$incFiles[$incFile]) {
                        $incFiles[$incFile] = true;

                        $code .= "include 'res://.inc/" . $incFile . "'; \n";

                        Logger::debug("Add '{$incFile}' to bootstrap script.");
                    }
                }
            });
        }

        $moduleClasses = [];

        foreach ($this->getModuleClasses($project) as $class) {
            if ($this->isModuleSingleton($project, $class)) {
                $moduleClasses[] = $class;
            }
        }

        $code .= "\n\$app->loadModules(" . var_export($moduleClasses, true) . ');';

        $guiStyles = $project->fetchNamedList('guiStyles');
        foreach ($guiStyles as $resPath => $filePath) {
            $code .= "\n\$app->addStyle('$resPath');";
        }

        /** @var File[] $skinFiles */
        $skinFiles = fs::scan($project->getSrcFile('.theme/skin/'), [
            'extensions' => ['css'], 'excludeDirs' => true
        ], 1);

        foreach ($skinFiles as $skinFile) {
            $name = str::replace($skinFile->getName(), ' ', '%20');
            $code .= "\n\$app->addStyle('/.theme/skin/{$name}');";
        }

        $code .= "\n\$app->addStyle('/.theme/style.fx.css');";

        $template->setInnerCode($code);
        $project->defineFile('src/JPHP-INF/.bootstrap.php', $template, true);
    }


    /**
     * @param Project $project
     * @return string
     */
    public function getAppModuleClass(Project $project): string
    {
        return "{$project->getPackageName()}\\modules\\AppModule";
    }


    /**
     * @param Project $project
     * @param $fullClass
     * @return string
     */
    public function getModuleShortClass(Project $project, $fullClass): string
    {
        $prefix = "{$project->getPackageName()}\\modules\\";

        if (str::startsWith($fullClass, $prefix)) {
            return str::sub($fullClass, str::length($prefix));
        }

        return $fullClass;
    }

    /**
     * @return ProjectFile|File
     */
    public function getModuleDirectory(Project $project): ?object
    {
        return $project->getFile("src/{$project->getPackageName()}/modules");
    }

    /**
     * @param Project $project
     * @return string[]
     * @throws \php\lang\IllegalArgumentException
     */
    public function getModuleFiles(Project $project): array
    {
        return Ide::get()->getFilesOfFormat(ScriptModuleFormat::class, $this->getModuleDirectory($project));
    }

    /**
     * @param Project $project
     * @return array
     * @throws \php\lang\IllegalArgumentException
     */
    public function getModuleClasses(Project $project): array
    {
        $files = $this->getModuleFiles($project);

        $classes = [];

        foreach ($files as $file) {
            $item = FileUtils::relativePath($project->getFile('src'), $file);
            $classes[] = str::replace(fs::pathNoExt($item), '/', '\\');
        }

        return $classes;
    }

    /**
     * @param $fullClass
     * @return bool
     * @throws \Exception
     */
    public function isModuleSingleton(Project $project, $fullClass): bool
    {
        if ($fullClass == $this->getAppModuleClass($project)) {
            return true;
        }

        $fullClass = fs::normalize($fullClass);

        $metaFile = $project->getSrcFile("$fullClass.module");

        if ($metaFile->isFile()) {
            if ($meta = Json::fromFile($metaFile)) {
                return (bool) $meta['props']['singleton'];
            }
        }

        return false;
    }

    /**
     * @param Project $project
     * @return ScriptModuleEditor[]
     * @throws \php\lang\IllegalArgumentException
     */
    public function getModuleEditors(Project $project): array
    {
        $editors = [];

        foreach ($this->getModuleFiles($project) as $file) {
            $editor = FileSystem::fetchEditor($file, true);
            $editors[FileUtils::hashName($file)] = $editor;
        }

        return $editors;
    }

    public function hasModule(Project $project, $name)
    {
        return $project->getFile("src/{$project->getPackageName()}/modules/$name.php")->isFile();
    }

    /**
     * @param $name
     * @param bool $cache
     * @return ScriptModuleEditor|null
     */
    public function getModuleEditor(Project $project, $name, $cache = false): ?ScriptModuleEditor
    {
        $editor = FileSystem::fetchEditor($project->getFile("src/{$project->getPackageName()}/modules/$name.php"), $cache);

        if ($editor) {
            return $editor instanceof ScriptModuleEditor ? $editor : null;
        } else {
            return null;
        }
    }

    public function createModule(Project $project, $name)
    {
        if ($this->hasModule($project, $name)) {
            $editor = $this->getModuleEditor($project, $name);
            if ($editor) {
                $editor->delete(true);
            }
        }

        Logger::info("Creating module '$name' ...");

        $template = new PhpClassFileTemplate($name, 'AbstractModule');
        $template->setNamespace("{$project->getPackageName()}\\modules");


        $php = PhpProjectBehaviour::get();

        if ($php && $php->getImportType() == 'package') {
            $template->setImports([
                "std, gui, framework, {$project->getPackageName()}"
            ]);
        } else {
            $template->setImports([
                'php\gui\framework\AbstractModule'
            ]);
        }

        $file = $project->createFile("src/{$project->getPackageName()}/modules/$name.php", $template);

        Json::toFile(
            $project->getFile("src/{$project->getPackageName()}/modules/$name.module"), ['props' => [], 'components' => []]
        );

        if (!$file->exists()) {
            $file->applyTemplate($template);
            $file->updateTemplate(true);
        }

        Logger::info("Finish creating module '$name'");

        $project->save();

        return $file;
    }

    public function getFormDirectory(Project $project)
    {
        return $project->getFile("src/{$project->getPackageName()}/forms");
    }

    public function getFormFiles(Project $project)
    {
        return Ide::get()->getFilesOfFormat(GuiFormFormat::class, $this->getFormDirectory($project));
    }


    /**
     * @return \ide\editors\FormEditor[]
     * @throws IdeException
     */
    public function getFormEditors(Project $project): array
    {
        $editors = [];

        foreach ($this->getFormFiles($project) as $filename) {
            $editor = FileSystem::fetchEditor($filename, true);

            if ($editor) {
                if (!($editor instanceof FormEditor)) {
                    throw new IdeException("Invalid format for -> $filename");
                }

                $editors[FileUtils::hashName($filename)] = $editor;
            }
        }

        return $editors;
    }

    /**
     * @param $moduleName
     * @return FormEditor[]
     * @throws IdeException
     */
    public function getFormEditorsOfModule(Project $project, string $moduleName): array
    {
        $formEditors = $this->getFormEditors($project);

        $result = [];

        foreach ($formEditors as $formEditor) {
            $modules = $formEditor->getModules();

            if ($modules[$moduleName]) {
                $result[FileUtils::hashName($formEditor->getFile())] = $formEditor;
            }
        }

        return $result;
    }


    public function hasForm(Project $project, $name)
    {
        return $project->getFile("src/{$project->getPackageName()}/forms/$name.php")->isFile();
    }

    /**
     * @param $name
     * @return FormEditor|null
     */
    public function getFormEditor(Project $project, $name): ?FormEditor
    {
        $editor = $this->hasForm($project, $name) ?
            FileSystem::fetchEditor($project->getFile("src/{$project->getPackageName()}/forms/$name.php"), true)
            : null;

        return $editor instanceof FormEditor ? $editor : null;
    }

    public function createForm(Project $project, $name, $namespace = null)
    {
        if ($this->hasForm($project, $name)) {
            $editor = $this->getFormEditor($project, $name);
            $editor->delete(true);
        }

        Logger::info("Creating form '$name' ...");

        $namespace = $namespace ?: "{$project->getPackageName()}\\forms";

        $file = $project->getSrcFile(str::replace($namespace, '\\', '/') . "/$name");

        $project->createFile($project->getAbsoluteFile("$file.fxml"), new GuiFormFileTemplate());

        $template = new PhpClassFileTemplate($name, 'AbstractForm');

        $template->setNamespace($namespace);

        $php = PhpProjectBehaviour::get();

        if ($php && $php->getImportType() == 'package') {
            $template->setImports([
                "std, gui, framework, {$project->getPackageName()}"
            ]);
        } else {
            $template->setImports([
                'php\gui\framework\AbstractForm'
            ]);
        }

        $sources = $project->createFile($project->getAbsoluteFile("$file.php"), $template);
        $sources->applyTemplate($template);
        $sources->updateTemplate(true);

        Logger::info("Finish creating form '$name'");

        $project->save();

        return $sources;
    }

    /**
     * @param AbstractEditor|null $contextEditor
     * @return array
     * @throws IdeException
     */
    public function getAllPrototypes(Project $project, AbstractEditor $contextEditor = null): array
    {
        $elements = [];

        foreach ($this->getFormEditors($project) as $editor) {
            if ($contextEditor && FileUtils::hashName($contextEditor->getFile()) == FileUtils::hashName($editor->getFile())) {
                continue;
            }

            if ($editor->getConfig()->get('form.withPrototypes')) {
                foreach ($editor->getObjectList() as $it) {
                    if ($it->element && $it->element->canBePrototype()) {
                        $it->group = $editor->getTitle();
                        $it->value = "{$it->getGroup()}.{$it->value}";
                        $elements[] = $it;
                    }
                }
            }
        }

        return $elements;
    }

    /**
     * @param $id
     * @return array [element => AbstractFormElement, behaviours => [[value, spec], ...]]
     */
    public function getPrototype(Project $project, string $id): ?array
    {
        list($group, $id) = str::split($id, '.', 2);

        if ($editor = $this->getFormEditor($project, $group)) {
            $result = [];

            $objects = $this->getObjectList($project, $editor);

            foreach ($objects as $one) {
                if ($one->text == $id) {
                    $result['version'] = $one->version;
                    $result['element'] = $one->element;
                    break;
                }
            }

            $result['behaviours'] = [];

            foreach ($editor->getBehaviourManager()->getBehaviours($id) as $one) {
                $result['behaviours'][] = [
                    'value' => $one,
                    'spec' => $editor->getBehaviourManager()->getBehaviourSpec($one),
                ];
            }

            return $result;
        }

        return null;
    }

    /**
     * @param $fileName
     * @return ObjectListEditorItem[]
     */
    public function getObjectList(Project $project, $fileName)
    {
        $result = [];

        $index = $project->getIndexer()->get($project->getAbsoluteFile($fileName), '_objects');

        foreach ((array) $index as $it) {
            /** @var AbstractFormElement $element */
            $element = class_exists($it['type']) ? new $it['type']() : null;

            $result[] = $item = new ObjectListEditorItem(
                $it['id'], null
            );

            $item->hint = $element ? $element->getName() : '';
            $item->element = $element;
            $item->version = (int)$it['version'];
            $item->rawType = $it['type'];

            if ($element) {
                if ($graphic = $element->getCustomPreviewImage((array)$it['data'])) {
                    $item->graphic = $graphic;
                } else {
                    $item->graphic = $element->getIcon();
                }
            }
        }

        return $result;
    }

    public function saveStylesheet(Project $project)
    {
        $styleFile = $project->getSrcFile('.theme/style.fx.css');

        if (!fs::exists($styleFile)) {
            fs::delete($project->data(self::class . '#ideStyleFile'));
        }
    }

    private function applyStylesheetToEditor(Project $project, AbstractEditor $editor)
    {
        $styleFile = $project->getSrcFile('.theme/style.fx.css');

        /** @var File[] $skinFiles */
        $skinFiles = fs::scan($project->getSrcFile('.theme/skin/'), [
            'extensions' => ['css'], 'excludeDirs' => true
        ], 1);

        $path = $styleFile->toUrl();

        $stylesheets = $editor->getStylesheets();
        foreach ($stylesheets as $stylesheet) {
            $editor->removeStylesheet($stylesheet);
        }

        $resource = new ResourceStream('/ide/formats/form/FormEditor.css');
        $editor->removeStylesheet($resource->toExternalForm());
        $editor->removeStylesheet($path);
        $editor->addStylesheet($resource->toExternalForm());

        $guiStyles = $project->fetchNamedList('guiStyles');
        foreach ($guiStyles as $resPath => $filePath) {
            $editor->addStylesheet($filePath);
        }

        /*foreach ($stylesheets as $stylesheet) {
            if (str::contains($stylesheet, '/skin/')) {
                $editor->removeStylesheet($stylesheet);
            }
        }*/

        foreach ($skinFiles as $file) {
            $editor->addStylesheet($file->toUrl());
        }

        if (fs::isFile($styleFile)) {
            $editor->addStylesheet($path);
        }
    }

    public function reloadStylesheet(Project $project)
    {
        if (!UXApplication::isUiThread()) {
            uiLater(fn() => $this->reloadStylesheet($project));
            return;
        }

        Logger::info("Reload stylesheet");

        $this->saveStylesheet($project);

        foreach (FileSystem::getOpenedEditors() as $editor) {
            $this->applyStylesheetToEditor($project, $editor);
        }

        $file = $project->getSrcFile('.theme/style.fx.css');
        $project->data(self::class . '#ideStyleFileTime', $file->isFile() ? fs::time($file) : -1);
    }

    public function reloadStylesheetIfModified(Project $project)
    {
        $ideStyleFileTime = $project->data(self::class . '#ideStyleFileTime');

        if (!$ideStyleFileTime) {
            $this->reloadStylesheet($project);
            return;
        }

        $styleFile = $project->getSrcFile('.theme/style.fx.css');

        if (!$styleFile->exists()) {
            if ($ideStyleFileTime != -1) {
                $this->reloadStylesheet($project);
            }
        } else if (fs::time($styleFile) != $ideStyleFileTime) {
            $this->reloadStylesheet($project);
        }
    }


    /**
     * Возвращает текущий скин проекта или скин по умолчанию
     * Используется при сборке и запуске проекта, для копировании файлов скина в проект
     * @return ProjectSkin
     * @throws \php\io\IOException
     */
    public function getCurrentSkin(Project $project): ?ProjectSkin
    {
        $skinDir = $project->getSrcFile('.theme/skin');

        if (!fs::isDir($skinDir) ||
            !fs::isFile("$skinDir/skin.json") ||
            !fs::isFile("$skinDir/skin.css")) {
            return null;
        }

        try {
            $skin = ProjectSkin::createFromDir($skinDir);
            return $skin;
        } catch (IOException $e) {
            Logger::warn("Unable to read skin information, {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Удалить скин программы.
     * И установить скин по умолчанию
     * @param Project $project
     * @throws \Exception
     */
    public function clearSkin(Project $project)
    {
        $skinDir = $project->getSrcFile('.theme/skin');
        fs::clean($skinDir);
        fs::delete($skinDir);

        $this->reloadStylesheet($project);
    }


    /**
     * Применить скин к программе.
     * @param ProjectSkin $skin
     * @throws \Exception
     */
    public function applySkin(Project $project, ProjectSkin $skin)
    {
        if ($skin->hasAnyScope('gui')) {
            $skinDir = $project->getSrcFile('.theme/skin');
            fs::clean($skinDir);
            fs::makeDir($skinDir);

            try {
                $skin->unpack($skinDir);
                $this->reloadStylesheet($project);
            } catch (ZipException $e) {
                uiLaterAndWait(function () use ($e) {
                    MessageBoxForm::warning(
                        _("message.gui.skin.error.cannot.unpack.skin.archive::Ошибка установки скина, невозможно распаковать архив с файлами скина.") . "\n\n -> {$e->getMessage()}"
                    );
                });
            }
        } else {
            uiLaterAndWait(function () {
                MessageBoxForm::warning('message.gui.skin.cannot.apply.to.project::Данный скин невозможно применить к проекту данного типа.');
            });
        }
    }

    /**
     * Конвертирует скин в тему проекта.
     */
    public function convertSkinToTheme(Project $project)
    {
        if ($this->getCurrentSkin($project)) {
            FileUtils::copyDirectory(
                $project->getSrcFile('.theme/skin'),
                $project->getSrcFile('.theme')
            );

            fs::delete($project->getSrcFile('.theme/style.fx.css'));
            fs::rename($project->getSrcFile('.theme/skin.css'), 'style.fx.css');

            $this->clearSkin($project);
        }
    }

    public function getCustomFonts(Project $project): array
    {
        $result = [];

        $fontsDir = $project->getSrcFile(".data/fonts/");
        foreach (fs::scan($fontsDir, ['extensions' => ['ttf']], 1) as $font) {
            $result[] = "/.data/fonts/$font";
        }

        return $result;
    }
}
