<?php
namespace ide\formats;

use ide\editors\AbstractEditor;
use ide\editors\CodeEditor;
use ide\editors\MonacoCodeEditor;
use php\lib\arr;
use php\lib\fs;

/**
 * Class PhpCodeFormat
 * @package ide\formats
 */
class JPPMPackageFormat extends AbstractFormat
{
    /**
     * @param $file
     *
     * @param array $options
     * @return AbstractEditor
     * @throws \Exception
     */
    public function createEditor($file, array $options = [])
    {
        $editor = new MonacoCodeEditor($file);
        $editor->setLanguage("yaml");

        return $editor;
    }

    public function getIcon()
    {
        return 'icons/property16.png';
    }

    /**
     * @param $file
     *
     * @return bool
     */
    public function isValid($file)
    {
        return arr::has([ "package.php.yml", "package-lock.php.yml" ], fs::name($file));
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