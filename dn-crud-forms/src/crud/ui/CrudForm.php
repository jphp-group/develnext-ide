<?php
namespace crud\ui;

use crud\ui\CrudUI;
use php\gui\framework\AbstractForm;
use php\gui\layout\UXAnchorPane;
use php\gui\UXForm;

/**
 * Class CrudForm
 * @package crud
 */
class CrudForm extends UXForm
{
    protected CrudPane $pane;

    /**
     * CrudForm constructor.
     * @param CrudPane $crudPane
     */
    public function __construct(CrudPane $crudPane)
    {
        parent::__construct();

        $this->layout = $this->pane = $crudPane;
    }
}