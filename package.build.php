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
use php\lib\fs;

function task_publish(Event $e)
{
    Tasks::runExternal('./dn-app-framework', 'publish', [], ...$e->flags());
    Tasks::runExternal('./dn-designer', 'publish', [], ...$e->flags());
    Tasks::runExternal('./dn-gui-tabs-ext', 'publish', [], ...$e->flags());

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
 * @jppm-task build-ide
 */
function task_buildIde(Event $e)
{
    Tasks::runExternal("./ide", "install");

    Tasks::copy("./ide/vendor", "./ide/build/vendor/");
    Tasks::copy('./ide/misc', './ide/build/');

    Tasks::deleteFile('./dn-launcher/build');
    Tasks::runExternal('./dn-launcher', 'build');
    Tasks::deleteFile("./ide/build/DevelNext.jar");
    Tasks::copy('./dn-launcher/build/DevelNext.jar', './ide/build');

    Tasks::runExternal('./ide', 'copySourcesToBuild');

    $os = $e->isFlag('linux') ? 'linux' : 'win';

    $jrePath = $e->package()->getAny("jre.$os");

    if ($jrePath) {
        if (fs::isDir("./tools/build/jre/$os")) {
            Tasks::copy("./tools/build/jre/$os", "./ide/build/jre");
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

            Tasks::copy("./tools/build/jre/$os", "./ide/build/jre");
        }
    }
}
