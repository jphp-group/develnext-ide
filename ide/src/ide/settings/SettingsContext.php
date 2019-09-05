<?php

namespace ide\settings;

use ide\Ide;
use ide\Logger;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class SettingsContext {

    /**
     * @param AbstractSettingsItem|AbstractSettingsGroup $item
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
     * @param array $value
     */
    public function setValue(array $value) {
        try {
            Ide::get()->setUserConfigValue("settings." . $this->contextId, $value);
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getValue(): array {
        try {
            return Ide::get()->getUserConfigArrayValue("settings." . $this->contextId. $key);
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
            return [];
        }
    }
}