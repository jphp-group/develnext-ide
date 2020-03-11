<?php
namespace ide\formats;

use ide\editors\AbstractEditor;
use ide\editors\MonacoCodeEditor;
use ide\utils\FileUtils;
use php\lib\Str;

class CssCodeFormat extends AbstractFormat
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

    public function getTitle($path)
    {
        if (Str::endsWith(FileUtils::normalizeName($path), ".theme/style.css")) {
            return "CSS Стиль";
        }

        return parent::getTitle($path);
    }


    /**
     * @param $file
     *
     * @return bool
     */
    public function isValid($file)
    {
        return Str::endsWith($file, '.css');
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