<?php
namespace crud\ui\editors;

use crud\Crud;
use crud\CrudField;
use php\gui\UXNode;

/**
 * Class AbstractCrudEditor
 */
abstract class AbstractCrudEditor
{
    /**
     * @var Crud
     */
    private $crud;

    /**
     * @var CrudField
     */
    private $field;

    /**
     * @var object
     */
    protected $context;

    public function __construct(Crud $crud, CrudField $field)
    {
        $this->crud = $crud;
        $this->field = $field;
    }

    abstract public function makeUI(): UXNode;

    abstract public function setValueForUI(UXNode $editorUi, $value);
    abstract public function getValueFromUI(UXNode $editorUi);

    /**
     * @param object $context
     */
    public function setContext(object $context): void
    {
        $this->context = $context;
    }

    /**
     * @return CrudField
     */
    final public function getField(): CrudField
    {
        return $this->field;
    }

    public function isLeftSideUI(): bool
    {
        return false;
    }

    public function isWithoutLabel(): bool
    {
        return false;
    }

    /**
     * @return Crud
     */
    protected function getCrud(): Crud
    {
        return $this->crud;
    }
}