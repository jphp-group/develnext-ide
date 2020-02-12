<?php
namespace dn\packr;

use compress\TarArchive;
use httpclient\HttpClient;
use packager\Colors;
use packager\Event;
use packager\JavaExec;
use packager\Package;
use packager\Vendor;
use packager\cli\Console;
use php\format\JsonProcessor;
use php\lang\Process;
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
     * @throws \php\format\ProcessorException
     * @throws \php\io\IOException
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

        if (!$pkg->getAny('packr.enabled', true)) {
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
        $rcEditBin = $vendor->getFile($packrPkg, 'rcedit-x86.exe')->getAbsoluteFile();

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

        $icons = $pkg->getAny('app.launcher.icons', []);
        $winIcon = flow($icons)->findOne(function ($el) { return fs::ext($el) === "ico"; });
        $macIcon = flow($icons)->findOne(function ($el) { return fs::ext($el) === "icns"; });

        if (fs::isFile($macIcon)) {
            if (Package::isMac()) {
                $config['icon'] = $macIcon;
            }
        } else {
            Console::warn("Failed to find icon '{0}' for mac executable", $macIcon);
        }

        $outputDir = fs::parent(fs::abs($buildDir)) . "/" . fs::name($buildDir) . "-" . $pkg->getOS();
        Tasks::deleteFile($outputDir);

        $config['output'] = $outputDir;
        $packrConfig = $buildDir . "/packr-" . $pkg->getOS() . ".json";
        fs::formatAs($packrConfig, $config, 'json');

        $process = $javaExec->run([fs::abs($packrConfig)], fs::abs($buildDir));
        $process = $process->inheritIO()->startAndWait();

        if ($winIcon) {
            Console::info("Add icon " . Colors::withColor($winIcon, 'white') . " for '$buildFileName.exe'");
            $this->addWinIcon($rcEditBin, fs::abs("$outputDir/$buildFileName.exe"), fs::abs($winIcon));
        }

        // move jars to libs
        $separatedBuild = $pkg->getAny('packr.separated-build', true);

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

        Tasks::deleteFile($packrConfig);

        if ($process->getExitValue() != 0) {
            exit($process->getExitValue());
        }
    }

    protected function addWinIcon($rcEditBin, $exeFile, $iconFile)
    {
        if (!fs::isFile($iconFile)) {
            Console::warn("Failed to find icon '{0}' for exe", $iconFile);
            return;
        }

        $proc = new Process([$rcEditBin, $exeFile, '--set-icon', $iconFile], fs::parent($exeFile));
        $proc = $proc->inheritIO()->startAndWait();

        if ($proc->getExitValue() !== 0) {
            Console::warn("Failed to add icon to '{0}'", $exeFile);
        }
    }
}