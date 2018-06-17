<?php

use ide\Ide;
use ide\IdeClassLoader;
use ide\Logger;
use ide\systems\IdeSystem;
use php\gui\UXDialog;
use php\gui\UXNode;
use php\lang\System;

$cache = false;//!IdeSystem::isDevelopment();

if (System::getProperty('develnext.noCodeCache')) {
    $cache = false;
}

//$cache = true; //  TODO delete it.

$loader = new IdeClassLoader($cache, IdeSystem::getOwnLibVersion());
$loader->register(true);

IdeSystem::setLoader($loader);

if (!IdeSystem::isDevelopment()) {
    Logger::setLevel(Logger::LEVEL_INFO);
}

$app = new Ide();
$app->addStyle('/.theme/style.css');
$app->launch();

/**
 * @param $code
 * @param array ...$args
 * @return string|UXNode
 */
function _($code, ...$args) {
    $ideLocalizer = Ide::get()->getLocalizer();

    if (!$ideLocalizer->language) {
        return $code;
    }

    if ($code instanceof UXNode) {
        $ideLocalizer->translateNode($code, ...$args);
        return $code;
    } else {
        return $ideLocalizer->translate($code, $args);
    }
}

function dump($arg)
{
    ob_start();

        var_dump($arg);
        $str = ob_get_contents();

    ob_end_clean();

    UXDialog::showAndWait($str);
}

/**
 * @param $name
 * @return \php\gui\UXImageView
 */
function ico($name)
{
    return Ide::get()->getImage("icons/$name.png");
}