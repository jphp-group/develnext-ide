<?php
namespace php\framework;

use php\io\Stream;
use php\time\Time;
use php\lib\char;

/**
 * Class Logger
 * @package php\framework
 * @packages framework
 */
class Logger
{
    protected static $ANSI_CODES = array(
        "off"        => 0,
        "bold"       => 1,
        "italic"     => 3,
        "underline"  => 4,
        "blink"      => 5,
        "inverse"    => 7,
        "hidden"     => 8,
        "gray"       => 30,
        "red"        => 31,
        "green"      => 32,
        "yellow"     => 33,
        "blue"       => 34,
        "magenta"    => 35,
        "cyan"       => 36,
        "silver"     => "0;37",
        "white"      => 37,
        "black_bg"   => 40,
        "red_bg"     => 41,
        "green_bg"   => 42,
        "yellow_bg"  => 43,
        "blue_bg"    => 44,
        "magenta_bg" => 45,
        "cyan_bg"    => 46,
        "white_bg"   => 47,
    );

    const LEVEL_ERROR = 1;
    const LEVEL_WARN = 2;

    const LEVEL_INFO = 100;
    const LEVEL_DEBUG = 200;

    protected static $level = self::LEVEL_INFO;
    protected static $showTime = false;
    protected static $colored = true;

    protected static function withColor($str, $color)
    {
        $color_attrs = explode("+", $color);
        $ansi_str = "";

        foreach ($color_attrs as $attr) {
            $ansi_str .= char::of(27) . "[" . self::$ANSI_CODES[$attr] . "m";
        }

        $ansi_str .= $str . char::of(27) . "[" . self::$ANSI_CODES["off"] . "m";
        return $ansi_str;
    }

    public static function setColored(boolean $colored)
    {
        self::$colored = $colored;
    }

    public static function isColored()
    {
        return self::$colored;
    }

    /**
     * @return int
     */
    public static function getLevel()
    {
        return self::$level;
    }

    /**
     * @param int $level
     */
    public static function setLevel($level)
    {
        self::$level = $level;
    }

    /**
     * @return boolean
     */
    public static function isShowTime()
    {
        return self::$showTime;
    }

    /**
     * @param boolean $showTime
     */
    public static function setShowTime($showTime)
    {
        self::$showTime = $showTime;
    }

    static protected function getLogName($level)
    {
        switch ($level) {
            case self::LEVEL_DEBUG: return "DEBUG";
            case self::LEVEL_INFO: return "INFO";
            case self::LEVEL_ERROR: return "ERROR";
            case self::LEVEL_WARN: return "WARN";
            default:
                return "UNKNOWN";
        }
    }

    static protected function getLogColor($level) {
        switch ($level) {
            case self::LEVEL_DEBUG: return "silver";
            case self::LEVEL_WARN: return "yellow";
            case self::LEVEL_ERROR: return "red";
            default:
                return null;
        }
    }

    static protected function log($level, $message)
    {
        if ($level <= static::$level) {
            $time = "";

            if (static::$showTime) {
                $time = "(" . Time::now()->toString('HH:mm:ss') . ") ";
            }

            $line = "[" . static::getLogName($level) . "] $time" . $message . "\r\n";
            $_line = $line;

            if (self::$colored) {
                if ($color = static::getLogColor($level)) {
                    $_line = static::withColor($line, $color);
                }
            }

            static $out = null;

            if (!$out) {
                $out = Stream::of('php://stdout');
            }

            $out->write($_line);
        }
    }

    static function info($message)
    {
        static::log(self::LEVEL_INFO, $message);
    }

    static function debug($message)
    {
        static::log(self::LEVEL_DEBUG, $message);
    }

    static function warn($message)
    {
        static::log(self::LEVEL_WARN, $message);
    }

    static function error($message)
    {
        static::log(self::LEVEL_ERROR, $message);
    }
}