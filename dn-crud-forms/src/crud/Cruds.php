<?php
namespace crud;


use crud\ui\editors\ActionsCrudEditor;
use crud\ui\editors\BooleanCrudEditor;
use crud\ui\editors\EnumCrudEditor;
use crud\ui\editors\EnumSetCrudEditor;
use crud\ui\editors\IntegerCrudEditor;
use crud\ui\editors\LabelCrudEditor;
use crud\ui\editors\PathCrudEditor;
use crud\ui\editors\ProgressCrudEditor;
use crud\ui\editors\SeparatorCrudEditor;
use crud\ui\editors\StringCrudEditor;

class Cruds
{
    static public function create(): Crud
    {
        $crud = new Crud();
        $crud->addEditor('sep', SeparatorCrudEditor::class);
        $crud->addEditor('string', StringCrudEditor::class);
        $crud->addEditor('boolean', BooleanCrudEditor::class);
        $crud->addEditor('bool', BooleanCrudEditor::class);
        $crud->addEditor('integer', IntegerCrudEditor::class);
        $crud->addEditor('int', IntegerCrudEditor::class);
        $crud->addEditor('enum', EnumCrudEditor::class);
        $crud->addEditor('enum-set', EnumSetCrudEditor::class);
        $crud->addEditor('path', PathCrudEditor::class);
        $crud->addEditor('label', LabelCrudEditor::class);
        $crud->addEditor('progress', ProgressCrudEditor::class);

        $crud->addEditor('actions', ActionsCrudEditor::class);

        return $crud;
    }
}