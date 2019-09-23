<?php
namespace ide\doc;

use ide\AbstractExtension;

/**
 * @deprecated
 * Class DocExtension
 * @package ide\doc
 */
class DocExtension extends AbstractExtension
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
        return "plugin.doc.name";
    }

    public function getDescription(): string {
        return "plugin.doc.description";
    }
}
