<?php
namespace ide\formats\sprite;

use php\gui\UXNode;
use php\lib\str;
use php\util\Flow;
use php\xml\DomElement;

/**
 * Class SpriteSpec
 * @package ide\formats\sprite
 */
class SpriteSpec
{
    public $name = "";
    public $file = null;
    public $schemaFile = null;
    public $frameWidth = 0;
    public $frameHeight = 0;

    public $speed = 12;

    /**
     * @var int[]
     */
    public $animations = [];

    public $fixtureType = null;
    public $fixtureData = [];

    public $metaCentred = true;

    public $metaAutoSize = false;

    public $defaultAnimation = null;

    /**
     * @param $name
     * @param DomElement $element
     */
    function __construct($name, DomElement $element = null)
    {
        $this->name = $name;

        if ($element) {
            $this->file = $element->getAttribute('file');

            $this->frameWidth = (int)$element->getAttribute('frameWidth');
            $this->frameHeight = (int)$element->getAttribute('frameHeight');
            $this->defaultAnimation = $element->getAttribute('defaultAnimation');
            $this->metaCentred = (bool) $element->getAttribute('metaCentred');
            $this->metaAutoSize = (bool) $element->getAttribute('metaAutoSize');

            if ($element->hasAttribute('speed')) {
                $this->speed = (int)$element->getAttribute('speed');
            }

            $this->readFixture($element);
            $this->readAnimation($element);
        }
    }

    private function readFixture(DomElement $element)
    {
        if ($element->getAttribute('fixtureType')) {
            $this->fixtureType = $element->getAttribute('fixtureType');

            $point = [];
            $data  = [];

            foreach (str::split($element->getAttribute('fixtureData'), ',') as $p) {
                $point[] = $p;

                if (sizeof($point) == 2) {
                    $data[] = $point;
                    $point = [];
                }
            }

            if (sizeof($data) == 1) {
                $data = $data[0];
            }

            $this->fixtureData = $data;
        }
    }

    private function readAnimation(DomElement $element)
    {
        /** @var DomElement $domAnimation */
        foreach ($element->findAll('./animation') as $domAnimation) {
            $name = $domAnimation->getAttribute('name');

            $this->animations[$name] = Flow::of(Str::split($domAnimation->getAttribute('indexes'), ','))->map(function ($one) {
                return (int)trim($one);
            })->toArray();
        }
    }
}