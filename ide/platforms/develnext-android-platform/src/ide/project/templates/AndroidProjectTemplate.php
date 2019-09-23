<?php

namespace ide\project\templates;

use ide\formats\templates\JPPMPackageFileTemplate;
use ide\Ide;
use ide\project\AbstractProjectTemplate;
use ide\project\Project;
use php\gui\UXDialog;
use php\gui\UXNode;
use php\lang\Process;
use php\lang\System;
use php\lib\str;

class AndroidProjectTemplate extends AbstractProjectTemplate {

    /**
     * @return UXNode|string
     * @throws \Exception
     */
    public function getName() {
        return _("project.template.android.name");
    }

    /**
     * @return UXNode|string
     * @throws \Exception
     */
    public function getDescription() {
        return _("project.template.android.description");
    }

    public function getIcon32() {
        return "icons/android32.png";
    }

    public function getIcon() {
        return "icons/android16.png";
    }

    public function getSupportContext(): string {
        return "android";
    }

    /**
     * @param Project $project
     *
     * @return void
     * @throws \php\lang\IllegalArgumentException
     * @throws \php\lang\IllegalStateException
     */
    public function makeProject(Project $project) {
        $this->makePackageFile($project);

        $id  = UXDialog::input("ID Приложения", "org.develnext." . $project->getName());
        $sdk = UXDialog::input("Android SDK", "");

        $this->runTask($project, "update");
        $this->runTask($project, "android:init", [
            "ANDROID_HOME" => $sdk,
            "JPHP_ANDROID_SDK" => 28,
            "JPHP_ANDROID_SDK_TOOLS" => "29.0.0",
            "JPHP_ANDROID_APPLICATION_ID" => $id,
            "JPHP_ANDROID_UI" => "javafx"
        ]);
    }

    /**
     * @param Project $project
     * @return mixed
     */
    public function recoveryProject(Project $project) {

    }

    /**
     * @param Project $project
     */
    private function makePackageFile(Project $project)
    {
        $file = $project->getFile("package.php.yml");

        if ($file->exists()) return;

        $pkgFile = new JPPMPackageFileTemplate($file);

        $pkgFile->useProject($project);
        $pkgFile->setPlugins([
            'App'
        ]);

        $pkgFile->setSources([
            "src"
        ]);

        $pkgFile->setIncludes([
            'index.php'
        ]);

        $pkgFile->setDeps([
            "jphp-runtime" => "*"
        ]);

        $pkgFile->setDevDeps([
            "jppm-android-plugin" => "*"
        ]);

        $pkgFile->save();
    }

    /**
     * @param Project $project
     * @param string $task
     * @param array $env
     * @return int|null
     * @throws \php\lang\IllegalArgumentException
     * @throws \php\lang\IllegalStateException
     */
    private function runTask(Project $project, string $task, array $env = []) {
        $processString = "jppm " . $task;

        if (Ide::get()->isWindows())
            $processString = "cmd.exe /c " . $processString;

        $process = new Process(str::split($processString, " "), $project->getRootDir(), flow(System::getEnv(), $env)->toMap());
        $process = $process->inheritIO()->startAndWait();

        return $process->getExitValue();
    }
}