<?php
use packager\Event;

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
