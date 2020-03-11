<?php
namespace crud;

use php\lib\str;

class CrudEntity
{
    protected string $name;

    protected array $window = [];

    /**
     * @var CrudField[]
     */
    protected $fields = [];

    /**
     * CrudEntity constructor.
     * @param array $schema
     */
    public function __construct(array $schema = [])
    {
        if ($schema) {
            $this->load($schema);
        }
    }

    /**
     * @param array $schema
     */
    public function load(array $schema)
    {
        $this->name = $schema['name'];
        $this->window = (array) $schema['window'];

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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getWindow(): array
    {
        return $this->window;
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