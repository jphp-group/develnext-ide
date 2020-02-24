<?php
namespace crud\ui;

use crud\Crud;
use crud\CrudEntity;
use crud\ui\editors\AbstractCrudEditor;
use php\gui\layout\UXHBox;
use php\gui\layout\UXPane;
use php\gui\layout\UXVBox;
use php\gui\UXLabel;
use php\gui\UXSeparator;

class CrudUI
{
    /**
     * @var CrudEntity
     */
    protected $crudEntity;
    protected $entity;

    /**
     * @var Crud
     */
    protected $crud;

    /**
     * @var AbstractCrudEditor[]
     */
    protected $editors = [];
    protected $editorUIs = [];

    protected $eventHandlers = [];

    /**
     * CrudUI constructor.
     * @param Crud $crud
     * @param CrudEntity $crudEntity
     * @param $entity
     * @throws \crud\CrudException
     */
    public function __construct(Crud $crud, CrudEntity $crudEntity)
    {
        $this->crudEntity = $crudEntity;
        $this->crud = $crud;

        foreach ($crudEntity->getFields() as $code => $field) {
            if ($field) {
                $this->editors[$code] = $crud->createEditor($field->editor, $field);
                $this->editors[$code]->setContext($this);
            } else {
                $this->editors[$code] = null;
            }
        }
    }

    public function trigger($action)
    {
        foreach ((array) $this->eventHandlers[$action] as $handler) {
            $handler($this);
        }
    }

    public function on($action, callable $callback)
    {
        $this->eventHandlers[$action][] = $callback;
    }

    public function off($action)
    {
        $this->eventHandlers[$action] = [];
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Load values from entity to UI
     */
    public function load()
    {
        $entity = $this->entity;

        $getter = fn($code) => $entity[$code];

        if (is_object($entity)) {
            $getter = fn($code) => $entity->{$code};
        }

        foreach ($this->editorUIs as $code => $editorUI) {
            $editor = $this->editors[$code];
            $editor->setValueForUI($editorUI, $getter($code));
        }
    }

    /**
     * Save values for UI to entity
     */
    public function save()
    {
        $setter = fn($code, $value) => $this->entity[$code] = $value;

        if (is_object($this->entity)) {
            $setter = fn($code, $value) => $this->entity->{$code} = $value;
        }

        foreach ($this->editorUIs as $code => $editorUI) {
            $editor = $this->editors[$code];
            $value = $editor->getValueFromUI($editorUI);
            $setter($code, $value);
        }

        return $this->entity;
    }

    public function makeUi(): UXPane
    {
        $vbox = new UXVBox([], 10);
        $withOffset = false;

        /** @var AbstractCrudEditor $editor */
        foreach ($this->editors as $code => $editor) {
            if ($editor) {
                $editorUi = $editor->makeUi();

                UXHBox::setHgrow($editorUi, 'ALWAYS');

                $this->editorUIs[$code] = $editorUi;
                $editorUi->classes->addAll(["editor-$code"]);

                $crudField = $this->crudEntity->getFields()[$code];
                $labelStr = $this->crud->t($crudField->label);

                if ($editor->isWithoutLabel()) {
                    $itemUi = $editorUi;
                    $withOffset = true;
                } else {
                    if ($editor->isLeftSideUI()) {
                        $label = new UXLabel($labelStr);
                        $itemUi = new UXHBox([$editorUi, $label], 5);
                    } else {
                        $label = new UXLabel($labelStr . ":");
                        if ($crudField->hint) {
                            $hintLabel = new UXLabel("* " . $this->crud->t($crudField->hint));
                            $hintLabel->textColor = 'gray';

                            $UXVBox = new UXVBox([$editorUi, $hintLabel], 3);
                            UXHBox::setHgrow($UXVBox, 'ALWAYS');
                            $itemUi = new UXHBox([$label, $UXVBox], 5);
                        } else {
                            $itemUi = new UXHBox([$label, $editorUi], 5);
                        }
                    }

                    $itemUi->alignment = 'BASELINE_LEFT';

                    if ($withOffset) {
                        $itemUi->paddingLeft = 20;
                    }
                }

                UXVBox::setVgrow($itemUi, 'ALWAYS');
            } else {
                $itemUi = new UXSeparator();
                $itemUi->padding = 10;
            }

            $vbox->add($itemUi);
        }

        return $vbox;
    }
}