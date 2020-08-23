<?php

use compress\ArchiveEntry;
use compress\Bzip2InputStream;
use compress\GzipInputStream;
use compress\Lz4InputStream;
use compress\TarArchive;
use compress\ZipArchive;
use packager\Event;
use packager\cli\Console;
use packager\Package;
use php\io\Stream;
use php\lib\arr;
use php\lib\fs;
use php\lib\str;
use php\util\Regex;

function task_sandbox(Event $e)
{
    if (!$e->isFlag('l', 'light')) {
        Tasks::run('publish', [], 'y');
        Tasks::runExternal('./sandbox', 'update', []);
    }
    Tasks::runExternal('./sandbox', 'start', []);
}