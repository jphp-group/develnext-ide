<?php
namespace develnext\bundle\sql;

use ide\bundle\AbstractJarBundle;
use ide\project\Project;

class SqlBundle extends AbstractJarBundle
{
    function getName()
    {
        return "JPHP SQL Extension";
    }

    public function isAvailable(Project $project)
    {
        return true;
    }
}