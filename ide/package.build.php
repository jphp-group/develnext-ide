<?php

use php\lib\fs;
use php\lib\str;

function task_copySourcesToBuild($e)
{
    foreach ($e->package()->getAny('sources', []) as $src) {
        if (str::startsWith($src, '..')) continue;

        if (str::startsWith($src, "platforms/")) {
            $to = $src;

            if (str::endsWith($src, '/src')) {
                $to = fs::parent($src);
            }

            Tasks::copy("./$src", "./build/sources/$to");
        } else {
            Tasks::copy("./$src", "./build/sources/$src");
        }
    }

    Tasks::copy("../dn-app-framework/src", "./build/sources/dn-app-framework");
    Tasks::copy("./src-release", "./build/sources/src");
}
