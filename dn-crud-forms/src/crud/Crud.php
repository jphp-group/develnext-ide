<?php
namespace crud;

use crud\ui\editors\AbstractCrudEditor;

class Crud
{
    /**
     * @var AbstractCrudEditor[]
     */
    protected $editors = [];

    /**
     * @var callable
     */
    protected $localizationFunc = null;

    public function createEditor(string $code, CrudField $crudField): ?AbstractCrudEditor
    {
        if ($editorClass = $this->editors[$code]) {
            $editor = new $editorClass($this, $crudField);
            if ($editor instanceof AbstractCrudEditor) {
                return $editor;
            } else {
                throw new CrudException("$editorClass should extends " . AbstractCrudEditor::class);
            }
        }

        throw new CrudException("Editor '$code' is not found");
    }

    public function addEditor(string $code, string $editorClass)
    {
        $this->editors[$code] = $editorClass;
    }

    /**
     * @param callable|null $localizationFunc
     */
    public function setLocalizationFunc(?callable $localizationFunc): void
    {
        $this->localizationFunc = $localizationFunc;
    }

    /**
     * @param $text
     * @return mixed|void
     */
    public function t($text)
    {
        if (!$this->localizationFunc) return $text;

        return call_user_func($this->localizationFunc, $text);
    }
}