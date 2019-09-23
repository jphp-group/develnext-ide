<?php

namespace ide\settings;

use ide\Ide;
use ide\Logger;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class SettingsContext {

    /**
     * @param AbstractSettingsItem|AbstractSettingsGroup|string $item
     * @return SettingsContext
     */
    public static function of($item) {
        if (is_string($item))
            $item = new $item();

        return new SettingsContext($item->getName());
    }

    /**
     * @var string
     */
    private $contextId;

    /**
     * SettingsContext constructor.
     * @param string $contextId
     */
    public function __construct(string $contextId) {
        $this->contextId = $contextId;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setValue(string $key, string $value) {
        try {
            Ide::get()->setUserConfigValue($this->contextId . ".$key", $value);
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function getValue(string $key): ?string {
        try {
            return Ide::get()->getUserConfigValue($this->contextId . ".$key");
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
            return null;
        }
    }
}