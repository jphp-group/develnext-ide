<?php

use php\gui\layout\UXAnchorPane;
use php\gui\monaco\CompletionItem;
use php\gui\UXApplication;
use php\gui\monaco\MonacoEditor;
use php\gui\UXForm;

UXApplication::launch(function (UXForm $form) {
    $form->title = "MonacoEditor!";
    $editor = new MonacoEditor();
    $editor->backgroundColor = "#333";
    $editor->getEditor()->currentLanguage = "php";
    $editor->getEditor()->currentTheme = "vs-dark";

    $editor->getEditor()->document->text = "<?php\necho \"Hello, Word\";";
    $editor->setOnLoad(function () use ($editor) {
        $editor->getEditor()->registerCompletionItemProvider("php", function ($positionAndRange) {
            $item = new CompletionItem();
            $item->label = "test";
            $item->kind = 5; // from https://microsoft.github.io/monaco-editor/api/enums/monaco.languages.completionitemkind.html
            $item->documentation = "test 123";
            $item->insertText = "position: lineNumber: " . $positionAndRange["position"]["lineNumber"] . ", column: " . $positionAndRange["position"]["column"];

            $for = new CompletionItem();
            $for->label = "for";
            $for->kind = 17;
            $for->documentation = "for loop";
            $for->insertText = "for ()";
            return [
                $item,
                $for
            ];
        });
    });
    /*$editor->getEditor()->document->addTextChangeListener(function ($oldValue, $newValue) use ($editor) {
        Stream::putContents("./package.php.yml", $newValue);
    });*/
    //$editor->getEditor()->readOnly = true;

    /*\php\time\Timer::every("2s", function () use ($editor) {
        UXApplication::runLater(function () use ($editor) {
            $range = ['selectionStartLineNumber' => 5, 'selectionStartColumn' => 1, 'positionLineNumber' => 5, 'positionColumn' => 1000];
            $editor->getEditor()->setSelection($range);
            $editor->getEditor()->revealLineInCenter(5); // jump to 5 line
        });
    });*/

    UXAnchorPane::setAnchor($editor, 0);
    $form->add($editor);
    $form->show();
});
