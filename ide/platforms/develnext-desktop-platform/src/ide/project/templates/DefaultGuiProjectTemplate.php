<?php
namespace ide\project\templates;

use ide\editors\FormEditor;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\project\AbstractProjectTemplate;
use ide\project\behaviours\BackupProjectBehaviour;
use ide\project\behaviours\BundleProjectBehaviour;
use ide\project\behaviours\GuiFrameworkProjectBehaviour;
use ide\project\behaviours\JavaPlatformBehaviour;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\behaviours\RunBuildProjectBehaviour;
use ide\project\behaviours\ShareProjectBehaviour;
use ide\project\Project;
use ide\project\supports\JPPMProjectSupport;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use ide\utils\Json;
use php\lib\fs;
use php\lib\str;

/**
 * Class DefaultGuiProjectTemplate
 * @package ide\project\templates
 */
class DefaultGuiProjectTemplate extends AbstractProjectTemplate
{
    public function getName()
    {
        return _('project.template.gui.name');
    }

    public function getDescription()
    {
        return _('project.template.gui.description');
    }

    public function getIcon()
    {
        return 'icons/program16.png';
    }

    public function getIcon32()
    {
        return 'icons/programEx32.png';
    }

    public function getSupportContext(): string
    {
        return 'desktop';
    }

    public function openProject(Project $project)
    {
        $this->makePackageFile($project);
    }

    public function recoveryProject(Project $project)
    {
        $ideVersionHash = $project->getConfig()->getIdeVersionHash();

        $this->makePackageFile($project, $ideVersionHash < 2017022512);

        if (!$project->hasBehaviour(PhpProjectBehaviour::class)) {
            $project->register(new PhpProjectBehaviour(), false);
        }

        if (!$project->hasBehaviour(JavaPlatformBehaviour::class)) {
            $project->register(new JavaPlatformBehaviour(), false);
        }

        if (!$project->hasBehaviour(GuiFrameworkProjectBehaviour::class)) {
            $project->register(new GuiFrameworkProjectBehaviour(), false);
        }

        if (!$project->hasBehaviour(RunBuildProjectBehaviour::class)) {
            $project->register(new RunBuildProjectBehaviour(), false);
        }

        if (!$project->hasBehaviour(ShareProjectBehaviour::class)) {
            $project->register(new ShareProjectBehaviour(), false);
        }

        if (!$project->hasBehaviour(BackupProjectBehaviour::class)) {
            $project->register(new BackupProjectBehaviour(), false);
        }

        if ($ideVersionHash < 2017022512) {
            $this->migrateFrom16RC2($project);
        }

        if ($ideVersionHash < 2018013112) {
            $this->migrateFrom16x7($project);
        }
    }

    private function migrateFrom16RC2(Project $project)
    {
        $openedFiles = [];
        $selectedFile = $project->getConfig()->getSelectedFile();

        foreach ($project->getConfig()->getOpenedFiles() as $file) {
            $openedFiles[$file] = $file;
        }

        // migrate forms...
        fs::scan($project->getFile('src/.forms'), function ($filename) use ($project, &$openedFiles, &$selectedFile) {
            $ext = fs::ext($filename);

            if ($ext == 'fxml' || $ext == 'conf') {
                $path = $project->getAbsoluteFile($filename)->getRelativePath();

                fs::copy($filename, $file = $project->getFile('src/app/forms/' . fs::name($filename)));
                fs::delete($filename);

                if ($ext == 'fxml') {
                    $file = fs::pathNoExt($file) . '.php';

                    if ($openedFiles[$path]) {
                        $openedFiles[$file] = $file;
                        unset($openedFiles[$path]);
                    }

                    if ($selectedFile == $path) {
                        $selectedFile = $file;
                    }
                }
            }
        });

        // migrate modules
        foreach ($project->getFile('src/.scripts')->findFiles() as $file) {
            if ($file->isDirectory()) {
                $path = $project->getAbsoluteFile($file)->getRelativePath();

                $phpFile = $project->getFile('src/app/modules/' . $file->getName() . '.php');
                $jsonFile = $project->getFile('src/app/modules/' . $file->getName() . '.json');

                $jsonData = (array) Json::fromFile($jsonFile);

                $moduleMeta = [
                    'props' => (array) $jsonData['properties'], 'components' => []
                ];

                foreach ($file->findFiles() as $scriptFile) {
                    $meta = Json::fromFile($scriptFile);

                    if ($meta['type']) {
                        $meta['props'] = (array)$meta['properties'];

                        unset($meta['id'], $meta['ideType'], $meta['properties']);

                        $moduleMeta['components'][fs::nameNoExt($scriptFile)] = $meta;
                    }
                }

                Json::toFile(fs::pathNoExt($phpFile) . '.module', $moduleMeta);

                if ($openedFiles[$path]) {
                    unset($openedFiles[$path]);
                    $openedFiles[$path] = $phpFile;
                }

                if ($selectedFile == $path) {
                    $selectedFile = $phpFile;
                }

                fs::delete($jsonFile);
                FileUtils::deleteDirectory($file);
            }
        }


        foreach ($project->getFile('src/app/modules')->findFiles() as $file) {
            if (fs::ext($file) == 'php') {
                $metaFile = fs::pathNoExt($file) . '.module';

                if (!fs::isFile($metaFile)) {
                    $jsonFile = fs::pathNoExt($file) . '.json';

                    $meta = ['props' => [], 'components' => []];

                    if (fs::isFile($jsonFile)) {
                        if ($oldMeta = Json::fromFile($jsonFile)) {
                            $meta['props'] = (array)$meta['properties'];
                        }

                        fs::delete($jsonFile);
                    }

                    Json::toFile($metaFile, $meta);
                }
            }
        }

        FileUtils::deleteDirectory($project->getFile('src/.scripts'));

        fs::delete($project->getFile('src/.system/modules.json'));
        FileUtils::deleteDirectory($project->getFile('src/.gradle'));

        if (fs::isFile($styleFile = $project->getFile('src/.theme/style.css'))) {
            FileUtils::copyFile($styleFile, $project->getFile('src/.theme/style.fx.css'));
            fs::delete($styleFile);
        } else {
            FileUtils::put($project->getFile('src/.theme/style.fx.css'), "/* JavaFX CSS Style with -fx- prefix */\n\n");
        }

        fs::delete($project->getFile('src/.theme/style-ide.css'));

        $project->getConfig()->setTreeState(['/src/app/forms', '/src/app/modules']);
        $project->getConfig()->setOpenedFiles($openedFiles, $selectedFile);
        $project->getConfig()->save();
    }

    public function migrateFrom16x7(Project $project)
    {
        /** @var JPPMProjectSupport $jppm */
        //if ($jppm = $project->hasSupport('jppm')) {
            if ($project->hasBehaviour(BundleProjectBehaviour::class)) {
                $project->removeBehaviour(BundleProjectBehaviour::class);
            }
        //}
    }

    public function makePackageFile(Project $project, bool $addGame2D = false)
    {
        $file = $project->getFile("package.php.yml");

        if ($file->exists()) return;

        $pkgFile = new JPPMPackageFileTemplate($file);

        $pkgFile->useProject($project);
        $pkgFile->setPlugins(['App']);
        $pkgFile->setIncludes(['JPHP-INF/.bootstrap.php']);

        $deps = [
            'dn-app-framework' => '^1.0.0',
            'dn-debug-bundle' => '^0.1.0',
            'jphp-gui-desktop-ext' => '^1.0.0',
            'jphp-zend-ext' => '^1.0.0',
        ];

        $devDeps = [
            'dn-packr' => '^1.0.0'
        ];

        $depsUnix = ['jphp-gui-jfx-linux' => '^11.0.0'];
        $depsMac = ['jphp-gui-jfx-mac' => '^11.0.0'];
        $depsWin = ['jphp-gui-jfx-win' => '^11.0.0'];

        $extra = [
            'app' => [
                'jvm-args' => ['-Xms96m'],
                'build' => ['type' => 'multi-jar'],
                'launcher' => [
                    'icons' => ['launcher/app.ico', 'launcher/app.icns'],
                    'java' => ['embedded' => true]
                ],
            ],
            'packr' => ['enabled' => true, 'separated-build' => false]
        ];

        $bundles = [
            'develnext.bundle.game2d.Game2DBundle' => ['dn-game2d-bundle', '~1.0.0'],
            'ide.bundle.std.JPHPGuiDesktopBundle' => ['dn-game2d-bundle', '~1.0.0'],
            'develnext.bundle.jsoup.JsoupBundle' => ['dn-jsoup-bundle', '~1.0.0'],
            'develnext.bundle.hotkey.HotKeyBundle' => ['dn-hotkey-bundle', '~1.0.0'],
            'develnext.bundle.sql.FireBirdSqlBundle' => ['dn-firebirdsql-bundle', '~1.0.0'],
            'develnext.bundle.httpclient.HttpClientBundle' => ['dn-httpclient-bundle', '~1.0.0'],
            'develnext.bundle.jfoenix.JFoenixBundle' => ['dn-jfoenix-bundle', '^1.0.0'],
            'develnext.bundle.mail.MailBundle' => ['dn-mail-bundle', '~1.0.0'],
            'develnext.bundle.sql.MysqlBundle' => ['dn-mysql-bundle', '~1.0.0'],
            'develnext.bundle.sql.PgSqlBundle' => ['dn-pgsql-bundle', '~1.0.0'],
            'develnext.bundle.sql.SqliteBundle' => ['dn-sqlite-bundle', '~1.0.0'],
            'develnext.bundle.systemtray.SystemTrayBundle' => ['dn-systemtray-bundle', '~1.0.0'],
            'develnext.bundle.zip.ZipBundle' => ['dn-zip-bundle', '~1.0.0'],
        ];

        if ($addGame2D) {
            $deps['dn-game2d-bundle'] = '*';
        }

        foreach ($project->getIdeFile("bundles/")->findFiles() as $file) {
            if ($file->isFile() && fs::ext($file) === 'conf') {
                $bundleName = fs::nameNoExt($file);

                if ($bundles[$bundleName]) {
                    [$name, $version] = $bundles[$bundleName];
                    $deps[$name] = $version;

                    $file->delete();
                }
            }
        }

        $pkgFile->setDeps($deps);
        $pkgFile->setDevDeps($devDeps);

        $pkgFile->setDeps($depsWin, 'win');
        $pkgFile->setDeps($depsUnix, 'unix');
        $pkgFile->setDeps($depsMac, 'mac');
        $pkgFile->setExtra($extra);

        $pkgFile->save();
    }

    /**
     * @param Project $project
     *
     * @return Project
     */
    public function makeProject(Project $project)
    {
        /** @var BundleProjectBehaviour $bundle */
        //$bundle = $project->register(new BundleProjectBehaviour());

        $this->makePackageFile($project);

        /** @var PhpProjectBehaviour $php */
        $php = $project->register(new PhpProjectBehaviour());
        $project->register(new JavaPlatformBehaviour());

        /** @var GuiFrameworkProjectBehaviour $gui */
        $gui = $project->register(new GuiFrameworkProjectBehaviour());

        $project->register(new RunBuildProjectBehaviour());
        $project->register(new ShareProjectBehaviour());
        $project->register(new BackupProjectBehaviour());

        $project->setIgnoreRules([
            '*.log', '*.tmp'
        ]);

        $project->on('create', function () use ($gui, $bundle, $php, $project) {
            $php->setImportType('package');

            //$bundle->addBundle(Project::ENV_ALL, UIDesktopBundle::class, false);
            //$bundle->addBundle(Project::ENV_ALL, ControlFXBundle::class);
            //$bundle->addBundle(Project::ENV_ALL, Game2DBundle::class);

            //$project->makeDirectory('src/.theme/img');
            $styleFile = $project->getFile('src/.theme/style.fx.css');

            $rules = [
                '# Ignore rules for GIT (github.com, bitbucket.com, etc.)', '',
                '/.dn/cache', '/.dn/ide.lock', '/.dn/tmp', '/.dn/index.json', '/.dn/backup',
                '',
                '/vendor', '/src_generated', '/src/JPHP-INF', '/src/.debug', '/build', '/build.xml', '/build.gradle', '/settings.gradle',
                '',
                '*.log', '*.pid', '*.tmp', '*.sourcemap',
            ];

            FileUtils::putAsync(
                $project->getFile('.gitignore'), str::join($rules, "\n")
            );

            if (!$styleFile->exists()) {
                FileUtils::put($styleFile, "/* JavaFX CSS Style with -fx- prefix */\n\n");
            }

            $appModule  = $gui->createModule('AppModule');
            $mainModule = $gui->createModule('MainModule');
            $mainForm   = $gui->createForm('MainForm');

            $project->getConfig()->setTreeState([
                "/src/{$project->getPackageName()}/forms",
                "/src/{$project->getPackageName()}/modules",
                "/src/.theme",
            ]);

            $gui->setMainForm('MainForm');

            FileSystem::open($project->getMainProjectFile());
            FileSystem::open($mainModule);

            /** @var FormEditor $editor */
            $editor = FileSystem::open($mainForm);
            $editor->getConfig()->set('title', 'MainForm');
            $editor->addModule('MainModule');
            $editor->saveConfig();
        });

        return $project;
    }

    public function isProjectWillMigrate(Project $project)
    {
        // check is < 16.5
        if ($project->getConfig()->getIdeVersionHash() < 2017022512) {
            return true;
        }

        // check is < 17
        if ($project->getConfig()->getIdeVersionHash() < 2018013112) {
            return true;
        }

        return false;
    }
}