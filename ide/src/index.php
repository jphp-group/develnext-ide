<?php

use ide\Ide;
use ide\IdeClassLoader;
use ide\l10n\LocalizedString;
use ide\Logger;
use ide\systems\IdeSystem;
use php\gui\designer\UXDesignProperties;
use php\gui\UXDialog;
use php\gui\UXNode;
use php\lang\System;
use php\lib\str;

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
 * @return UXNode|string
 */
function _($code, ...$args) {
    $ideLocalizer = Ide::get()->getLocalizer();

    if (!$ideLocalizer->language) {
        return $code;
    }

    if ($code instanceof UXNode) {
        $ideLocalizer->translateNode($code, ...$args);
        return $code;
    } else if ($code instanceof \php\gui\UXTooltip) {
        $ideLocalizer->translateTooltip($code, ...$args);
        return $code;
    } else if ($code instanceof \php\gui\UXMenuItem) {
        $ideLocalizer->translateMenuItem($code, ...$args);
        return $code;
    } else if ($code instanceof \php\gui\UXMenu) {
        $ideLocalizer->translateMenu($code, ...$args);
        return $code;
    } else if ($code instanceof \php\gui\UXTab) {
        $ideLocalizer->translateTab($code, ...$args);

        return $code;
    } else if ($code instanceof UXDesignProperties) {
        return $ideLocalizer->translateDesignProperties($code);
    } else {
        $result = $ideLocalizer->translate($code, (array)$args);

        if (!($result instanceof LocalizedString)) {
            throw new Exception("$code result is not localized string");
        }

        return $result;
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
 * @return \php\gui\UXImageView|\php\gui\icons\UXFontAwesomeIcon|\php\gui\icons\UXIcons525Icon
 */
function ico($name)
{
    if (!$name) return null;
    if (is_object($name)) return $name;

    if (str::startsWith($name, 'fa:') || str::startsWith($name, '525:')) {
        [$type, $name] = str::split($name, ":", 2);
        [$name, $size, $color] = str::split($name, ",");

        $size = $size ?: "18px";

        switch ($type) {
            case "fa":
                return new \php\gui\icons\UXFontAwesomeIcon($name, $size, $color);
            case "525":
                return new \php\gui\icons\UXIcons525Icon($name, $size, $color);
        }

        return null;
    } else {
        if (str::startsWith($name, "icons/")) {
            return Ide::getImage($name);
        } else {
            return Ide::getImage("icons/$name.png");
        }
    }
}