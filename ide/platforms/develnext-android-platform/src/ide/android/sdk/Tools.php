<?php

namespace ide\android\sdk;

use ide\settings\items\AndroidSdkManagerItem;
use ide\settings\SettingsContext;
use php\lib\fs;

class Tools {
    private static $sdkLinks = [
        "windows" => "https://dl.google.com/android/repository/sdk-tools-windows-4333796.zip",
        "linux" => "https://dl.google.com/android/repository/sdk-tools-linux-4333796.zip",
        "darwin" => "https://dl.google.com/android/repository/sdk-tools-darwin-4333796.zip"
    ];

    /**
     * @param string $platform
     * @return string
     */
    public static function getSdkLink(string $platform): string {
        return self::$sdkLinks[$platform];
    }

    /**
     * @return string
     */
    public static function getSdkHome(): string {
        return SettingsContext::of(AndroidSdkManagerItem::class)->getValue("ANDROID_HOME");
    }

    /**
     * @return bool
     */
    public static function sdkExists(): bool {
        return fs::isDir(self::getSdkHome());
    }
}