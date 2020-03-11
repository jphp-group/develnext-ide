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
    $editor->getEditor()->document->addTextChangeListener(function ($oldValue, $newValue) {
        Stream::putContents("./package.php.yml", $newValue);
    });

    UXAnchorPane::setAnchor($editor, 0);
    $form->add($editor);
    $form->show();
});
