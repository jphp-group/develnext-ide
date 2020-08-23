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

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
    }

    /**
     * @return array
     */
    public function getSelection(): array
    {
    }

    /**
     * @param array $range
     */
    public function setSelection(array $range)
    {
    }

    /**
     * @return array
     */
    public function getPosition(): array
    {
    }

    /**
     * @return int
     */
    public function getPositionOffset(): int
    {
    }

    /**
     * @param array $position
     */
    public function setPosition(array $position)
    {
    }

    /**
     * @param int $lineNumber
     * @param int $type
     */
    public function revealLine(int $lineNumber, int $type = 0): void
    {
    }

    /**
     * @param int $lineNumber
     * @param int $type
     */
    public function revealLineInCenter(int $lineNumber, int $type = 0): void
    {
    }

    /**
     * @param int $lineNumber
     * @param int $type
     */
    public function revealLineInCenterIfOutsideViewport(int $lineNumber, int $type = 0): void
    {
    }

    /**
     * @param array $position [lineNumber => ..., column => ...]
     * @param int $type
     */
    public function revealPosition(array $position, int $type = 0): void
    {
    }

    public function trigger(string $action)
    {
    }

    public function focus()
    {
    }

    public function undo()
    {
    }

    public function redo()
    {
    }

    public function copy(): bool
    {
    }

    public function cut(): bool
    {
    }

    public function paste(): bool
    {
    }

    /**
     * @param string $language
     * @param array $triggerCharacters
     * @param callable $callback (array): CompletionItem[]
     * @param callable $resolveCallback (array): CompletionItem
     */
    public function registerCompletionItemProvider(string $language, string $triggerCharacters,
                                                   callable $callback, callable $resolveCallback)
    {
    }
}
