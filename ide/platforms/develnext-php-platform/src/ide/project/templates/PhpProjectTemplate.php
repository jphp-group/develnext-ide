<?php
namespace ide\project\templates;

use ide\formats\ProjectFormat;
use ide\formats\templates\JPPMPackageFileTemplate;
use ide\project\AbstractProjectTemplate;
use ide\project\behaviours\BackupProjectBehaviour;
use ide\project\behaviours\JavaPlatformBehaviour;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\control\CommonProjectControlPane;
use ide\project\Project;
use ide\utils\FileUtils;

class PhpProjectTemplate extends AbstractProjectTemplate
{
    /**
     * PhpProjectTemplate constructor.
     */
    public function __construct()
    {
    }

    public function getName()
    {
        return "PHP Проект";
    }

    public function getDescription()
    {
        return "Проект с исходниками в виде php файлов";
    }

    public function getIcon32()
    {
        return "icons/phpProject32.png";
    }

    public function openProject(Project $project)
    {
        /** @var ProjectFormat $registeredFormat */
        $registeredFormat = $project->getRegisteredFormat(ProjectFormat::class);

        if ($registeredFormat) {
            $registeredFormat->addControlPanes([
                new CommonProjectControlPane(),
            ]);
        }
    }

    public function makePackageFile(Project $project)
    {
        $file = $project->getFile("package.php.yml");

        if ($file->exists()) return;

        $pkgFile = new JPPMPackageFileTemplate($file);

        $pkgFile->useProject($project);
        $pkgFile->setPlugins(['App']);
        $pkgFile->setIncludes(['index.php']);

        $deps = [
            'jphp-core' => '*',
        ];

        $pkgFile->setDeps($deps);

        $pkgFile->save();
    }

    /**
     * @param Project $project
     *
     * @return Project
     */
    public function makeProject(Project $project)
    {
        /** @var PhpProjectBehaviour $php */
        $project->register(new JavaPlatformBehaviour());
        $php = $project->register(new PhpProjectBehaviour());
        $project->register(new BackupProjectBehaviour());

        $project->registerFormat(new ProjectFormat());

        $project->setIgnoreRules([
            '*.tmp'
        ]);

        $project->makeDirectory("src");
        FileUtils::putAsync($project->getFile("src/index.php"), "<?php\r\recho 'Hello World';\r");

        $this->makePackageFile($project);

        return $project;
    }

    /**
     * @param Project $project
     * @return mixed
     */
    public function recoveryProject(Project $project)
    {
        if (!$project->hasBehaviour(JavaPlatformBehaviour::class)) {
            $project->register(new JavaPlatformBehaviour(), false);
        }

        if (!$project->hasBehaviour(PhpProjectBehaviour::class)) {
            $project->register(new PhpProjectBehaviour(), false);
        }

        if (!$project->hasBehaviour(BackupProjectBehaviour::class)) {
            $project->register(new BackupProjectBehaviour(), false);
        }

        if (!$project->getRegisteredFormat(ProjectFormat::class)) {
            $project->registerFormat(new ProjectFormat());
        }
    }
}