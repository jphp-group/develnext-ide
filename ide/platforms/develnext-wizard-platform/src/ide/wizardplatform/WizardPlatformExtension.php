<?php
namespace ide\wizardplatform;

use ide\AbstractExtension;

class WizardPlatformExtension extends AbstractExtension
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
        return "plugin.platform.wizard.name";
    }

    public function getDescription(): string {
        return "plugin.platform.wizard.description";
    }
}