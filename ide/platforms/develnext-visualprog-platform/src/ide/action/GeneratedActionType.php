<?php
namespace ide\action;
use php\lang\Environment;

/**
 * Class GeneratedActionType
 * @package ide\action
 */
class GeneratedActionType extends AbstractSimpleActionType
{
    /**
     * @var array
     */
    private $data;

    /**
     * GeneratedActionType constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    function attributes()
    {
        $result = [];
        foreach ($this->data['attrs'] as $name => $attr) {
            $result[$name] = $attr['type'];
        }

        return $result;
    }

    function attributeLabels()
    {
        $result = [];
        foreach ($this->data['attrs'] as $name => $attr) {
            $result[$name] = $attr['label'];
        }

        return $result;
    }

    function getGroup()
    {
        return self::GROUP_SCRIPT;
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_DATA;
    }

    function getTagName()
    {
        return $this->data['code'];
    }

    function getTitle(Action $action = null)
    {
        return $this->data['title'];
    }

    function getDescription(Action $action = null)
    {
        return eval($this->data['description']);
    }

    function getIcon(Action $action = null)
    {
        return $this->data['icon'];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return eval($this->data['convertToCode']);
    }
}