<?php

namespace ide\commands\theme;

use ide\commands\theme\terminal\LightTerminalTheme;
use java\reflection\ReflectionClass;
use java\reflection\ReflectionTypes;

class LightTheme extends IDETheme {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return "Light";
    }

    /**
     * @inheritDoc
     */
    public function getAuthor(): string {
        return "DevelNext";
    }

    /**
     * @inheritDoc
     */
    public function getCSSFile(): string {
        return "/.theme/ide/light.css";
    }

    /**
     * @inheritDoc
     */
    public function getTerminalTheme() {
        return new LightTerminalTheme();
    }

    /**
     * @return ColorPalette
     */
    public function getColorPalette(): ColorPalette {
        return new LightColorPalette();
    }

    public function onApply() {
        ReflectionClass::forName("com.formdev.flatlaf.FlatLightLaf")
            ->getMethod("install", [])
            ->invoke(ReflectionTypes::getNull(), []);
    }
}
