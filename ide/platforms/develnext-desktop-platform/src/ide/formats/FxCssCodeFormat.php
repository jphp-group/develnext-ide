<?php
namespace ide\formats;

use ide\editors\AbstractEditor;
use ide\editors\MonacoCodeEditor;
use php\lib\Str;

class FxCssCodeFormat extends AbstractFormat
{
    /**
     * @param $file
     *
     * @param array $options
     * @return AbstractEditor
     * @throws \php\io\IOException
     */
    public function createEditor($file, array $options = [])
    {
        $monaco = new MonacoCodeEditor($file);
        $monaco->setLanguage("css");

        return $monaco;
    }


    /**
     * @param $file
     *
     * @return bool
     */
    public function isValid($file)
    {
        return str::endsWith($file, '.fx.css');
    }

    public function getIcon()
    {
        return 'icons/cssFile16.png';
    }

    /**
     * @param $any
     *
     * @return mixed
     */
    public function register($any)
    {

    }
}