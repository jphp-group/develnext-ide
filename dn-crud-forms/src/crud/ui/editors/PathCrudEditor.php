<?php
namespace crud\ui\editors;

use php\gui\designer\UXFileDirectoryTreeSource;
use php\gui\icons\UXFontAwesomeIcon;
use php\gui\layout\UXHBox;
use php\gui\UXButton;
use php\gui\UXDirectoryChooser;
use php\gui\UXFileChooser;
use php\gui\UXNode;
use php\gui\UXTextField;

class PathCrudEditor extends AbstractCrudEditor
{
    /**
     * @return UXNode
     */
    public function makeUI(): UXNode
    {
        $field = new UXTextField();
        $field->maxHeight = 999;

        $btn = new UXButton('...');
        $btn->font->bold = true;
        $btn->maxHeight = 999;

        $btn->on('click', function () use ($field) {
            if ($this->getField()->args['type'] === 'file') {
                $chooser = new UXFileChooser();
            } else {
                $chooser = new UXDirectoryChooser();
            }

            $chooser->title = $this->getField()->label;
            if ($file = $chooser->execute()) {
                $field->text = $file;
            }
        });

        $box = new UXHBox([$field, $btn], 5);

        UXHBox::setHgrow($field, 'ALWAYS');

        $box->alignment = 'BASELINE_LEFT';
        $box->fillHeight = true;

        return $box;
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
        /** @var UXHBox $editorUi */
        $editorUi->children[0]->text = $value;
    }

    public function getValueFromUI(UXNode $editorUi)
    {
        /** @var UXHBox $editorUi */
        return $editorUi->children[0]->text;
    }
}