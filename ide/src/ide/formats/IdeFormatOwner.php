<?php
namespace ide\formats;

use ide\Ide;
use ide\Logger;
use php\lib\reflect;

/**
 * Class IdeFormatOwner
 * @package ide\formats
 */
trait IdeFormatOwner
{
    /**
     * @var AbstractFormat[]
     */
    protected $formats = [];

    /**
     * @param AbstractFormat $format
     */
    public function registerFormat(AbstractFormat $format)
    {
        $class = reflect::typeOf($format);

        if (isset($this->formats[$class])) {
            return;
        }

        foreach ($format->getRequireFormats() as $el) {
            $this->registerFormat($el);
        }

        $this->formats[$class] = $format;
    }

    public function unregisterFormat(string $formatClass)
    {
        $format = $this->formats[$formatClass];

        if ($format) {
            foreach ($format->getRequireFormats() as $el) {
                $this->unregisterFormat(reflect::typeOf($el));
            }

            unset($this->formats[$formatClass]);
        } else {
            Logger::warn("Failed to unregister project format = $formatClass");
        }
    }

    /**
     * @param $class
     *
     * @return AbstractFormat
     */
    public function getRegisteredFormat($class)
    {
        return $this->formats[$class];
    }

    /**
     * @return AbstractFormat[]
     */
    public function getRegisteredFormats()
    {
        return $this->formats;
    }
}