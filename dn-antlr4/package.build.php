<?php

use compress\ZipArchive;
use compress\ZipArchiveEntry;
use packager\Event;
use packager\cli\Console;
use php\io\File;
use php\io\Stream;
use php\lang\Process;
use php\lang\System;
use php\lib\fs;
use php\lib\str;

/**
 * @jppm-task antlr4:build
 * @jppm-description Build grammars for ANTLR4
 * @jppm-dependency-of publish
 * @param Event $e
 */
function task_build_grammars(Event $e): void {
    // Build ANRLR4 runtime wrapper
    Tasks::runExternal("./jphp-antlr4-ext", "publish", [], 'yes');

    fs::makeDir("./jars/");
    fs::makeDir("./jars/.out/");

    if (!fs::isFile("./antlr-complete.jar")) {
        Console::log("Download ANTLR4 ...");

        Stream::putContents("./antlr-complete.jar",
            Stream::getContents("https://www.antlr.org/download/antlr-4.7.1-complete.jar"));
    }

    Console::log("Copy ANTLR4 grammars ...");
    Tasks::copy("./grammars/", "./jars/.out");
    Tasks::copy("./jphp-antlr4-ext/sdk/", "./sdk/");

    $files = fs::scan("./jars/.out/", function ($file) {
        if (fs::ext($file) == "g4") return $file;
    });

    Console::log("Generate java code from ANTLR4 grammars ...");
    foreach ($files as $file) {
        $processCommand = ["java", "-jar", fs::abs("./antlr-complete.jar"), fs::abs($file)];

        if (str::contains(str::lower(System::getProperty("os.name")), "win"))
            $processCommand = flow(["cmd.exe", "/c"], $processCommand)->toArray();

        $process = new Process($processCommand, "./jars/.out/");
        $process = $process->inheritIO()->startAndWait();
        $exit = $process->getExitValue();

        if ($exit != 0)
            exit($exit);
    }

    $jars = fs::scan("./jphp-antlr4-ext/jars/", function ($file) {
        if (fs::ext($file) == "jar") return fs::abs($file);
    });

    $files = fs::scan("./jars/.out/", function ($file) {
        if (fs::ext($file) == "java") return fs::abs($file);
    });

    Console::log("Compile java code from ANTLR4 grammars ...");
    $processCommand = flow(["javac", "-cp", str::join($jars, File::PATH_SEPARATOR)], $files)->toArray();

    if (str::contains(str::lower(System::getProperty("os.name")), "win"))
        $processCommand = flow(["cmd.exe", "/c"], $processCommand)->toArray();

    $process = new Process($processCommand, "./jars/.out/");
    $process = $process->inheritIO()->startAndWait();
    $exit = $process->getExitValue();

    if ($exit != 0)
        exit($exit);

    foreach ($files as $file)
        fs::delete($file);

    foreach ($jars as $jar) {
        $zip = new ZipArchive(fs::abs($jar));
        $zip->readAll(function (ZipArchiveEntry $entry, ?Stream $stream) {
            if (!$entry->isDirectory()) {
                $file = fs::abs('./jars/.out/' . $entry->name);
                if (!fs::isFile($file))
                    fs::makeFile($file);
                fs::copy($stream, $file);
            } else fs::makeDir(fs::abs('./jars/.out/' . $entry->name));
        });
    }

    fs::makeFile("./jars/grammars.jar");
    $jar = new ZipArchive(fs::abs("./jars/grammars.jar"));
    $jar->open();
    fs::scan(fs::abs("./jars/.out/"), function (string $file) use ($jar) {
        if (fs::isFile($file))
            $jar->addFile($file, fs::relativize($file, fs::abs("./jars/.out/")));
    });
    $jar->close();

    Tasks::cleanDir(fs::abs("./jars/.out/"), [], true);

    $plugin = new DefaultPlugin;
    $plugin->publish(new Event($e->packager(), $e->package(), [], [ "yes" => true ]));
}