<?php
namespace ide\editors\argument;

use php\gui\layout\UXHBox;
use php\gui\UXCheckbox;
use php\gui\UXComboBox;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\gui\UXToggleButton;
use php\gui\UXToggleGroup;
use php\lib\Items;
use php\lib\Str;

class TextMethodsArgumentEditor extends MethodsArgumentEditor
{
    static $variants = [
        'equals' => 'method.equals::Равно',
        'equalsIgnoreCase' => 'method.equals.i::Равно (без учета регистра)',
        'startsWith' => 'method.starts.with::Начинается с',
        'endsWidth' => 'method.ends.with::Кончается ...',
        'contains' => 'method.contains::Содержит',
        'regex' => 'entity.regular.expr::Регулярное выражение',
        'regexIgnoreCase' => 'entity.regular.expr.i::Регулярное выражение (без учета регистра)',
        'smaller' => 'method.smaller::Меньше',
        'greater' => 'method.greater::Больше',
    ];

    public function __construct(array $options = [])
    {
        parent::__construct(self::$variants);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'textMethods';
    }
}