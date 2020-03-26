<?php
namespace ide;

use ide\project\supports\JavaFXProjectSupport;
use ide\project\supports\jppm\JPPMAppPluginSupport;
use ide\project\supports\JPPMProjectSupport;
use ide\project\supports\PHPProjectSupport;

/**
 * Class PhpExtension
 * @package ide
 */
class PhpExtension extends AbstractExtension
{
    public function onRegister()
    {
        $ide = Ide::get();

        $ide->registerProjectSupport(PHPProjectSupport::class);
        $ide->registerProjectSupport(JPPMProjectSupport::class);
        $ide->registerProjectSupport(JPPMAppPluginSupport::class);
    }

    public function onIdeStart()
    {
    }

    public function onIdeShutdown()
    {
    }

    public function getName(): string {
        return "plugin.php.name";
    }

    public function getDescription(): string {
        return "plugin.php.description";
    }

    public function isSystem(): bool {
        return true;
    }
}