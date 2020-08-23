<?php
namespace ide\utils;

use php\util\Regex;


class MarkdownUtils
{
    private static $init = false;

    /** @var Regex */
    private static $varRegex;

    private static function init()
    {
        if (!self::$init) {
            self::$varRegex = new Regex("(\\$\\w[a-z0-9]{0,})", "im");
        }
    }

    public static function make(string $text): string
    {
        self::init();

        $text = self::$varRegex->with($text)->replaceWithCallback(function (Regex $pattern) {
            return "**" . Regex::quoteReplacement($pattern->group(1)) . "**";
        });


        return $text;
    }
}