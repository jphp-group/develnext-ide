<?php
namespace ide\bundle\std;

use ide\bundle\AbstractJarBundle;

class JPHPCoreBundle extends AbstractJarBundle
{
    function getName()
    {
        return "JPHP Core";
    }

    public function getDependencies()
    {
        return [
            JPHPRuntimeBundle::class
        ];
    }

    /**
     * @return array
     */
    function getJarDependencies()
    {
        return ['asm-all', 'jphp-core'];
    }

    public function getJPPMDependencies()
    {
        return [
            'jphp-core' => '*'
        ];
    }
}
