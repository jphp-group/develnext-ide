<?php

use compress\ArchiveEntry;
use compress\Bzip2InputStream;
use compress\GzipInputStream;
use compress\Lz4InputStream;
use compress\TarArchive;
use compress\ZipArchive;
use packager\Event;
use packager\cli\Console;
use php\io\Stream;
use php\lib\arr;
use php\lib\fs;
use php\lib\str;
use php\util\Regex;

function task_publish(Event $e)
{
    Tasks::runExternal('./dn-app-framework', 'publish', [], ...$e->flags());
    Tasks::runExternal('./dn-designer', 'publish', [], ...$e->flags());
    Tasks::runExternal('./dn-gui-tabs-ext', 'publish', [], ...$e->flags());
    Tasks::runExternal('./dn-antlr4', 'antlr4:build', [], ...$e->flags());

    foreach ($e->package()->getAny('bundles', []) as $bundle) {
        Tasks::runExternal("./bundles/$bundle", 'publish', [], ...$e->flags());
    }
}

/**
 * @jppm-task hub:publish
 */
function task_hubPublish(Event $e)
{
    Tasks::runExternal('./dn-app-framework', 'hub:publish', [], ...$e->flags());

    foreach ($e->package()->getAny('bundles', []) as $bundle) {
        Tasks::runExternal("./bundles/$bundle", 'hub:publish', [], ...$e->flags());
    }
}

/**
 * @jppm-task bundle:publish
 * @jppm-description Local Publishing for ide bundles.
 */
function task_bundlePublish(Event $e)
{
    foreach ($e->package()->getAny('bundles', []) as $bundle) {
        Tasks::runExternal("./bundles/$bundle", 'publish', [], ...$e->flags());
    }
}

/**
 * @jppm-task prepare-ide
 * @param Event $e
 */
function task_prepareIde(Event $e)
{
    Tasks::run('publish', [], 'yes');
    Tasks::runExternal("./ide", "update");
}

/**
 * @jppm-task start-ide
 * @jppm-description Start IDE (DevelNext)
 */
function task_startIde(Event $e)
{
    Tasks::runExternal('./ide', 'start', $e->args(), ...$e->flags());
}

/**
 * @jppm-task fetch-messages
 * @jppm-description Fetch all language messages from sources
 */
function task_fetchMessages($e)
{
    $buildPath = $e->package()->getConfigBuildPath();

    $regex = new Regex('(\\"|\\\')([a-z]+\\.[a-z0-9\\.]+)((\\:\\:)(.+?))?(\\\'|\\")');

    $ignoreExts = [
        'php', 'tmp', 'conf', 'ini', 'json', 'source', 'css', 'pid', 'log', 'lock', 'ws', 'gradle', 'xml',
        'axml', 'behaviour', ''
    ];
    $ignoreExts = arr::combine($ignoreExts, $ignoreExts);

    $ignores = [
        'app.hash'    => 1, 'develnext.endpoint' => 1, 'os.name' => 1, 'os.user' => 1, 'os.version' => 1, 'java.version' => 1,
        'user.home'   => 1, 'hub.develnext.org' => 1, 'develnext.org' => 1, 'develnext.path' => 1, 'splash.avatar' => 1,
        'splash.name' => 1, 'script.name' => 1, 'script.desc' => 1, 'script.author' => 1, 'user.language' => 1,
        'ide.language' => 1,
    ];

    $data = [];
    $ruData = fs::parse("./ide/misc/languages/ru/messages.ini");

    $dirs = ["./ide/src", "./ide/platforms", "./bundles"];

    foreach ($dirs as $dir) {
        fs::scan($dir, [
            'excludeDirs' => true,
            'extensions'  => ['php', 'fxml', 'conf', 'xml'],
            'callback'    => function ($filename) use ($regex, $ignoreExts, $ignores, &$data, &$ruData) {
                //echo "-> ", $filename, "\n";
                $content = fs::get($filename);

                $r = $regex->with($content); //->withFlags('');

                foreach ($r->all() as $groups) {
                    $var = $groups[2];

                    if ($ignores[$var]) continue;
                    if (str::count($var, '.') === 1 && $ignoreExts[fs::ext($var)]) continue;

                    $data[$var] = '';

                    if (!$ruData[$var]) {
                        $ruData[$var] = $groups[5] ?? $var;
                    }
                }
            }
        ]);
    }

    Tasks::createFile("$buildPath/messages.ini");
    //Tasks::createFile("$buildPath/messages.ru.ini");

    ksort($data);
    ksort($ruData);

    //fs::format("$buildPath/messages.ini", $data);
    fs::format("$buildPath/messages.ini", $ruData);
}

/**
 * @jppm-task build-ide
 */
function task_buildIde(Event $e)
{
    fs::makeDir("./tools/build/jre/");

    Tasks::runExternal("./ide", "install");

    Tasks::copy("./ide/vendor", "./ide/build/vendor/");
    Tasks::copy('./ide/misc', './ide/build/');

    Tasks::deleteFile('./dn-launcher/build');
    Tasks::runExternal('./dn-launcher', 'build');
    Tasks::deleteFile("./ide/build/DevelNext.jar");
    Tasks::copy('./dn-launcher/build/DevelNext.jar', './ide/build');
    Tasks::runExternal('./ide', 'copySourcesToBuild');

    $os = $e->isFlag('linux') ? 'linux' : $e->isFlag('darwin') ? 'darwin' : 'win';

    $jreLink = $e->package()->getAny("jdk.$os.url");
    $jrePath = "./tools/build/jre/" . fs::name($jreLink);

    if (!fs::exists($jrePath)) {
        fs::makeFile($jrePath);
        Console::log("Download JDK for $os from $jreLink");
        Stream::putContents($jrePath, Stream::getContents($jreLink));
    }

    if ($jrePath) {
        if (fs::isDir("./tools/build/jre/$os")) {
            Tasks::copy("./tools/build/jre/$os/" . $e->package()->getAny("jdk.version"), "./ide/build/jre");
        } else {
            switch (fs::ext($jrePath)) {
                case 'xz':
                    $arch = new TarArchive(new Lz4InputStream($jrePath));
                    break;
                case 'gz':
                    $arch = new TarArchive(new GzipInputStream($jrePath));
                    break;
                case 'bz2':
                    $arch = new TarArchive(new Bzip2InputStream($jrePath));
                    break;
                case 'zip':
                    $arch = new ZipArchive($jrePath);
                    break;

                default:
                    Console::error("Unable to unpack '$jrePath', unknown archive format");
                    exit(-1);
            }

            $arch->readAll(function (ArchiveEntry $e, ?Stream $stream) use ($os) {
                if ($stream) {
                    Console::print("-> copy $e->name to jre dir ...");
                    fs::ensureParent("./tools/build/jre/$os/$e->name");
                    fs::copy($stream, "./tools/build/jre/$os/$e->name", null, 1024 * 1024 * 8);
                    Console::log(".. done.");
                }
            });

            Tasks::copy("./tools/build/jre/$os/" . $e->package()->getAny("jdk.$os.dir"), "./ide/build/jre");
        }
    }
}
