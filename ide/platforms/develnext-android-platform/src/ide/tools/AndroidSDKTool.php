<?php

namespace ide\tools;

use ide\android\sdk\Tools;
use ide\Ide;
use ide\scripts\elements\FileChooserScriptComponent;
use ide\tool\AbstractTool;
use ide\tool\AbstractToolInstaller;
use php\io\File;
use php\lang\Process;
use php\lib\fs;
use php\lib\str;

class AndroidSDKTool extends AbstractTool {

    /**
     * @param array $args
     * @param $workDirectory
     * @return Process
     * @throws \Exception
     */
    public function execute(array $args, $workDirectory = null) {
        $command = [];
        $sdkHome = fs::normalize(fs::abs(Tools::getSdkHome()));

        if (Ide::get()->isWindows()) {
            $command[] = "cmd.exe";
            $command[] = "/c";
            $command[] = $sdkHome . "/tools/bin/sdkmanager.bat";
            $command[] = "--sdk_root=" . $sdkHome;
        } else {
            (new File($sdkHome . "/tools/bin/sdkmanager"))->setExecutable(true);
            $command[] = $sdkHome . "/tools/bin/sdkmanager";
            $command[] = "--sdk_root=" . $sdkHome;
        }

        foreach ($args as $arg) {
            $command[] = $arg;
        }

        $env = Ide::get()->makeEnvironment();
        $env["ANDROID_HOME"] = $sdkHome;
        $env["JAVA_OPTS"] = "-XX:+IgnoreUnrecognizedVMOptions --add-modules java.se.ee";

        return new Process($command, $sdkHome, $env);
    }

    /**
     * @return bool
     */
    public function getName() {
        return "Android SDK";
    }

    /**
     * @return bool
     */
    public function isAvailable() {
        return false;
    }

    public function getIcon(): string {
        return "icons/android32.png";
    }

    /**
     * @return AbstractToolInstaller
     */
    public function setup() {
        return new AndroidSDKToolInstaller($this);
    }
}