<?php

namespace php\gui\monaco;

class CompletionItem
{
    public string $label;
    public int $kind;
    public string $documentation;
    public string $insertText;
    public bool $insertAsSnippet = false;
}