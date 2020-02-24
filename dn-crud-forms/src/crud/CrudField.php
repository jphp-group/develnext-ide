<?php
namespace crud;

class CrudField
{
    public $label = "";
    public $code = "";
    public $editor = "string";
    public $editable = true;
    public $required = false;
    public $hint = "";
    public $args = [];

    /**
     * @param array $props
     */
    public function setProperties(array $props)
    {
        foreach ($props as $k => $v) {
            $this->{$k} = $v;
        }
    }
}