<?php

namespace php\gui\monaco;

abstract class Editor
{
    /**
     * @var string
     */
    public string $currentTheme, $currentLanguage;

    public bool $readOnly;

    /**
     * @var Document
     */
    public Document $document;

    /**
     * @return ViewController
     */
    public function getViewController(): ViewController
    {
    }

    public function isInitialized(): bool
    {
    }

    public function getSelection(): array
    {
    }

    public function setSelection(array $range)
    {
    }

    public function revealLine(int $lineNumber, int $type = 0): void {}
    public function revealLineInCenter(int $lineNumber, int $type = 0): void {}
    public function revealLineInCenterIfOutsideViewport(int $lineNumber, int $type = 0): void {}
    public function revealPosition(int $lineNumber, int $type = 0): void {}
}
