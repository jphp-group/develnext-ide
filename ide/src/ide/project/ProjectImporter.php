<?php
namespace ide\project;

use compress\ZipArchive;
use compress\ZipArchiveEntry;
use ide\utils\FileUtils;
use php\compress\ZipFile;
use php\io\Stream;

class ProjectImporter
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }
    
    public function extract($projectDir)
    {
        $zip = new ZipArchive($this->file);

        $zip->readAll(function (ZipArchiveEntry $entry, Stream $stream) use ($projectDir) {
            if ($entry->isDirectory()) {
                FileUtils::deleteDirectory("{$projectDir}/{$entry->name}");
            } else {
                FileUtils::copyFile($stream, "{$projectDir}/{$entry->name}");
            }
        });
    }
}