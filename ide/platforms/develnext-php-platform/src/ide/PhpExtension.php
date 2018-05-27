<?php
namespace ide;

use ide\project\supports\JavaFXProjectSupport;
use ide\project\supports\JPPMProjectSupport;

/**
 * Class PhpExtension
 * @package ide
 */
class PhpExtension extends AbstractExtension
{
    public function onRegister()
    {
        Ide::get()->registerProjectSupport(JPPMProjectSupport::class);
        Ide::get()->registerProjectSupport(JavaFXProjectSupport::class);
    }

    public function onIdeStart()
    {
    }

    public function onIdeShutdown()
    {
    }
}