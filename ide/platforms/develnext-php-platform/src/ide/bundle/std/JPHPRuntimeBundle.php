<?php
namespace ide\bundle\std;

use ide\bundle\AbstractJarBundle;

class JPHPRuntimeBundle extends AbstractJarBundle
{
    function getName()
    {
        return "JPHP Runtime";
    }

    function getDescription()
    {
        return "JPHP Рантайм";
    }

    /**
     * @return array
     */
    function getJarDependencies()
    {
        return [
            'jphp-runtime'
        ];
    }

    function getProvidedJarDependencies()
    {
        return [
            'dn-php-stub', 'dn-jphp-stub'
        ];
    }

    public function getJPPMDependencies()
    {
        return [
            'jphp-runtime' => '*'
        ];
    }
}
