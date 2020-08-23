<?php

use php\gui\event\UXKeyEvent;
use php\gui\layout\UXAnchorPane;
use php\gui\monaco\CompletionItem;
use php\gui\UXApplication;
use php\gui\monaco\MonacoEditor;
use php\gui\UXClipboard;
use php\gui\UXContextMenu;
use php\gui\UXForm;
use php\gui\UXMenuItem;
use php\io\File;
use php\io\ResourceStream;
use php\lib\fs;

UXApplication::launch(function (UXForm $form) {
    $form->title = "MonacoEditor!";
    $editor = new MonacoEditor(/*(new ResourceStream('/monaco/index.html'))->toExternalForm()*/);

    $editor->backgroundColor = "#333";
    $editor->getEditor()->currentLanguage = "php";
    $editor->getEditor()->currentTheme = "vs-dark";

    $copyAction = function () use ($editor) {
        $editor->getEditor()->copy();
    };

    $pasteAction = function () use ($editor) {
        $editor->getEditor()->paste();
    };

    $cutAction = function () use ($editor) {
        $editor->getEditor()->cut();
    };

    $contextMenu = new UXContextMenu();
    $contextMenu->items->add($copy = new UXMenuItem("Copy"));
    $contextMenu->items->add($cut = new UXMenuItem("Cut"));
    $contextMenu->items->add($paste = new UXMenuItem("Paste"));
    $copy->on("action", $copyAction);
    $paste->on("action", $pasteAction);
    $cut->on("action", $cutAction);

    $contextMenu->items->add(UXMenuItem::createSeparator());

    $undo = new UXMenuItem("Undo");
    $contextMenu->items->add($undo);
    $undo->on("action", function () use ($editor) {
        $editor->getEditor()->undo();
    });

    $redo = new UXMenuItem("Redo");
    $contextMenu->items->add($redo);
    $redo->on("action", function () use ($editor) {
        $editor->getEditor()->redo();
    });

    $editor->contextMenu = $contextMenu;

    $editor->getEditor()->document->text = "<?php\necho \"Hello, Word\";\n// foobar";
    $editor->setOnLoad(function () use ($editor) {

        $editor->getEditor()->registerCompletionItemProvider("php", ": > $", function ($positionAndRange) use ($editor) {
            $item = new CompletionItem();
            $item->label = "test";
            $item->kind = 3; // from https://microsoft.github.io/monaco-editor/api/enums/monaco.languages.completionitemkind.html
            $item->documentation = "test **123**\n\nFoobar";
            $item->detail = "Test Type";
            $item->insertText = "position: lineNumber: " . $positionAndRange["position"]["lineNumber"] . ", column: " . $positionAndRange["position"]["column"] . ", pos: " . $editor->getEditor()->getPositionOffset();

            $snippet = new CompletionItem();
            $snippet->label = "my-third-party-library";
            $snippet->kind = 17;
            $snippet->documentation = "snippet test";
            $snippet->detail = "Foobar";
            $snippet->insertAsSnippet = true;
            $snippet->insertText = '"${1:my-third-party-library}": "latest"';

            return [
                $item,
                $snippet
            ];
        }, function ($data) use ($editor) {
            $item = new CompletionItem();
            $item->detail = "Hello World";
            $item->documentation = "Hey *hey* hey";
            return $item;
        });


        $editor->getEditor()->focus();
        $editor->getEditor()->setPosition(['lineNumber' => 2, 'column' => 3]);
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
