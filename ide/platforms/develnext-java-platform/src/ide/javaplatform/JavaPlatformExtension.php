<?php
namespace ide\javaplatform;

use ide\AbstractExtension;

class JavaPlatformExtension extends AbstractExtension
{
    public function onRegister()
    {

    }

    public function onIdeStart()
    {

    }

    public function onIdeShutdown()
    {

    }

    public function getName(): string {
        return "plugin.platform.java.name";
    }

    public function getDescription(): string {
        return "plugin.platform.java.description";
    }

    public function isSystem(): bool {
        return true;
    }
}