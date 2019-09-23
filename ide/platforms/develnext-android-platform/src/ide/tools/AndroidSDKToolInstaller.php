<?php

namespace ide\tools;

use compress\ZipArchive;
use compress\ZipArchiveEntry;
use ide\android\sdk\Tools;
use ide\Ide;
use ide\settings\items\AndroidSdkManagerItem;
use ide\settings\SettingsContext;
use ide\tool\AbstractToolInstaller;
use php\io\Stream;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;

class AndroidSDKToolInstaller extends AbstractToolInstaller {

    /**
     * @return Thread
     */
    public function run(): Thread {
        return new Thread(function () {
            $url = Tools::getSdkLink(
                Ide::get()->isWindows() ? "windows":
                    Ide::get()->isMac() ? "darwin": "linux"
            );

            $sdkToolsFile = Ide::get()->getFile("android-sdk.zip");
            $sdkDirectory = Ide::get()->getFile("android-sdk-home");

            if (!fs::isDir($sdkDirectory)) {
                fs::makeDir($sdkDirectory);
            }

            if (!$sdkToolsFile->exists()) {
                $this->triggerMessage("Download Android SDK from {$url} ...");
                fs::copy(Stream::of($url), $sdkToolsFile);
                $this->triggerMessage(".done\n");

                $this->triggerMessage("Unzip " . fs::abs($sdkToolsFile), "UNZIP");

                $zip = new ZipArchive(Stream::of($sdkToolsFile));
                $zip->readAll(function (ZipArchiveEntry $entry, ?Stream $stream) use ($sdkDirectory) {
                    $file = $sdkDirectory . "/{$entry->name}";
                    if ($entry->isDirectory())
                        fs::makeDir($file);
                    else {
                        fs::makeFile($file);
                        $this->triggerMessage("{$entry->name}", "UNZIP");
                        fs::copy($stream, $file);
                    }
                });
            }

            $this->triggerMessage("Setup develnext environment ...\n");
            SettingsContext::of(AndroidSdkManagerItem::class)->setValue("ANDROID_HOME", $sdkDirectory);

            $this->triggerMessage("Executing sdkmanager ...\n");

            $process = $this->getTool()->execute([
                "\"platform-tools\"", "\"platforms;android-29\"", "\"build-tools;29.0.0\""
            ])->start();

            $process->getInput()->eachLine(function ($line) use ($process) {
                if (str::contains($line, "not been accepted (y/N)?")) {
                    $process->getOutput()->write("y\n");
                }

                $this->triggerMessage($line . "\n");
            });

            if ($process->getExitValue() != 0) {
                uiLater(function () {
                    $this->triggerDone(false);
                });
            }

            uiLater(function () {
                $this->triggerDone(true);
            });
        });
    }
}