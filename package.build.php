<?php
use packager\Event;

function task_publish(Event $e)
{
    Tasks::runExternal('./dn-app-framework', 'publish', [], ...$e->flags());
}

/**
 * @jppm-task hub:publish
 */
function task_hubPublish(Event $e)
{
    Tasks::runExternal('./dn-app-framework', 'hub:publish', [], ...$e->flags());
}