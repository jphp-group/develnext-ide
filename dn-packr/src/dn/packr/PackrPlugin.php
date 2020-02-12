<?php
namespace dn\packr;

use compress\TarArchive;
use httpclient\HttpClient;
use packager\Event;
use packager\JavaExec;
use packager\Vendor;
use packager\cli\Console;
use php\format\JsonProcessor;
use php\lib\arr;
use php\lib\fs;
use php\lib\str;
use phpx\parser\ClassRecord;
use phpx\parser\SourceFile;
use phpx\parser\SourceManager;
use Tasks;

/**
 * @jppm-task build
 *
 * @jppm-task-prefix packr
 */
class PackrPlugin
{
    /**
     * @jppm-need-package
     *
     * @jppm-dependency-of build
     *
     * @jppm-description Build App Launcher via Packr
     * @param Event $event
     * @throws \php\lang\IllegalArgumentException
     * @throws \php\lang\IllegalStateException
     */
    public function build(Event $event)
    {
        $pkg = $event->package();

        if (flow($pkg->getAny('plugins'))->findValue('App') === null) {
            Console::error("Packr requires App plugin");
            exit(-1);
        }

        if (!$pkg->getAny('app.packer.enabled', true)) {
            Console::log('-> Skip Packr build (app.packer.enabled = false)');
            return;
        }

        $mainClass = $event->package()->getAny('app.main-class', 'php.runtime.launcher.Launcher');
        $build = $event->package()->getAny('app')['build'] ?: [];
        $buildType = $build['type'] ?? 'one-jar';

        if ($buildType !== "multi-jar") {
            Console::error("Packr requires 'app.build.type' as 'multi-jar' in App plugin config");
            exit(-1);
        }

        if (!$pkg->getAny('app.launcher.java.embedded')) {
            Console::error("Packr requires 'app.launcher.java.embedded' as true in App plugin config");
            exit(-1);
        }

        Tasks::run('app:build', $event->args(), ...$event->flags());

        $buildDir = $event->package()->getConfigBuildPath();
        $buildFileName = "{$event->package()->getName()}-{$event->package()->getVersion('last')}";

        if ($build['file-name']) {
            $buildFileName = $build['file-name'];
        }

        $vendor = new Vendor($pkg->getConfigVendorPath());
        $packrPkg = $vendor->getPackage('dn-packr');
        $packrJarFile = $vendor->getFile($packrPkg, 'packr.jar')->getAbsoluteFile();

        $packr = (array) $pkg->getAny('packr', []);
        $javaExec = new JavaExec($packrJarFile);

        $config = [];
        $config['mainclass'] = $mainClass;
        $config['jdk'] = fs::abs($buildDir);

        $config['executable'] = $buildFileName;
        $config['classpath'] = [];
        $config['verbose'] = $pkg->getAny('packr.verbose', true);
        $config['vmargs'] = flow($pkg->getAny('app.jvm-args', []))->map(function ($el) {
            return str::startsWith($el, '-') ? str::sub($el, 1) : $el;
        })->toArray();

        $classpath = flow(
            fs::scan("$buildDir/", ['extensions' => ['jar']], 1),
            fs::scan("$buildDir/libs/", ['extensions' => ['jar']], 1)
        );

        foreach ($classpath as $one) {
            $config['classpath'][] = fs::relativize($one, $buildDir);
        }

        $config['platform'] = 'windows64';
        switch ($pkg->getOS()) {
            case "win": $config['platform'] = 'windows64'; break;
            case "linux": $config['platform'] = 'linux64'; break;
            case "mac": $config['platform'] = 'mac'; break;
        }

        $outputDir = fs::parent(fs::abs($buildDir)) . "/" . fs::name($buildDir) . "-" . $pkg->getOS();
        Tasks::deleteFile($outputDir);

        $config['output'] = $outputDir;
        $packrConfig = $buildDir . "/packr-" . $pkg->getOS() . ".json";
        fs::formatAs($packrConfig, $config, 'json');

        $process = $javaExec->run([fs::abs($packrConfig)], fs::abs($buildDir));
        $process = $process->inheritIO()->startAndWait();

        // move jars to libs
        $separatedBuild = $pkg->getAny('packr.separated-build', false);

        if ($separatedBuild) {
            Tasks::createDir("$outputDir/libs");
        }

        $configJson = fs::parse("$outputDir/config.json");

        foreach ($configJson['classPath'] as &$value) {
            if ($value === "$buildFileName.jar") {
                continue;
            }

            if ($separatedBuild) {
                if (!fs::move("$outputDir/$value", "$outputDir/libs/$value")) {
                    Console::error("Failed to move '$outputDir/$value' to the '$outputDir/libs' dir");
                    exit(-1);
                }
            }

            $value = "libs/" . $value;
        }

        if ($separatedBuild) {
            fs::format("$outputDir/config.json", $configJson);
        } else {
            fs::format("$buildDir/config.json", $configJson);
            Tasks::copy("$outputDir/$buildFileName.exe", $buildDir);
            Tasks::deleteFile("$outputDir");
        }

        if ($process->getExitValue() != 0) {
            exit($process->getExitValue());
        }
    }
}