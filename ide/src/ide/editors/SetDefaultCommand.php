<?php

namespace ide\editors;

use ide\misc\AbstractCommand;
use ide\ui\elements\DNCheckbox;
use php\gui\UXApplication;
use php\gui\UXNode;

class SetDefaultCommand extends AbstractCommand
{
    /**
     * @var FormEditor
     */
    protected $formEditor;

    protected $editor;

    /**
     * SetDefaultCommand constructor.
     * @param FormEditor $formEditor
     * @param $editor
     */
    public function __construct(FormEditor $formEditor, $editor)
    {
        $this->formEditor = $formEditor;
        $this->editor = $editor;
    }

    public function getName()
    {
        return 'editor.use.by.default::Использовать по умолчанию';
    }

    public function makeUiForHead()
    {
        $ui = new DNCheckbox($this->getName());
        $ui->padding = 3;

        $ui->selected = $this->formEditor->getDefaultEventEditor(false) == "php";

        UXApplication::runLater(function () use ($ui) {
            $ui->watch('selected', function (UXNode $self, $property, $oldValue, $newValue) {
                if ($newValue) {
                    $this->formEditor->setDefaultEventEditor($this->editor);
                } else {
                    $this->formEditor->setDefaultEventEditor($this->editor == 'php' ? 'constructor' : 'php');
                }
            });
        });

        return $ui;
    }

    public function withBeforeSeparator()
    {
        return true;
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        //
    }
}