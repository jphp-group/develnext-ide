<?php

use php\gui\layout\UXAnchorPane;
use php\gui\UXApplication;
use php\gui\monaco\MonacoEditor;
use php\gui\UXForm;
use php\io\Stream;

UXApplication::launch(function (UXForm $form) {
    $form->title = "MonacoEditor!";
    $editor = new MonacoEditor();
    $editor->backgroundColor = "#333";
    $editor->getEditor()->currentLanguage = "yaml";
    $editor->getEditor()->currentTheme = "vs-dark";

    $editor->getEditor()->document->text = Stream::getContents("./package.php.yml");
    $editor->getEditor()->document->addTextChangeListener(function ($oldValue, $newValue) use ($editor) {
        Stream::putContents("./package.php.yml", $newValue);
    });
    $editor->getEditor()->readOnly = true;

    \php\time\Timer::every("2s", function () use ($editor) {
        UXApplication::runLater(function () use ($editor) {
            $range = ['selectionStartLineNumber' => 5, 'selectionStartColumn' => 1, 'positionLineNumber' => 5, 'positionColumn' => 1000];
            $editor->getEditor()->setSelection($range);
            $editor->getEditor()->revealLineInCenter(5); // jump to 5 line
        });
    });

    UXAnchorPane::setAnchor($editor, 0);
    $form->add($editor);
    $form->show();
});
