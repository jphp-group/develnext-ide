<?php
namespace crud;

use php\lib\str;

class CrudEntity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var CrudField[]
     */
    protected $fields = [];

    /**
     * @param array $schema
     */
    public function load(array $schema)
    {
        $this->name = $schema['name'];
        foreach ($schema['props'] as $i => $prop) {
            $code = $prop['code'];

            if (!$code) {
                $code = str::uuid();
            }

            if (!$prop) {
                $this->fields[$code] = null;
                continue;
            }

            $crudField = new CrudField();
            $crudField->setProperties((array) $prop);
            $crudField->code = $code;

            $this->fields[$code] = $crudField;
        }
    }

    /**
     * @return CrudField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * @param CrudField $crudField
     */
    public function addField(CrudField $crudField)
    {
        $this->fields[$crudField->code] = $crudField;
    }
}