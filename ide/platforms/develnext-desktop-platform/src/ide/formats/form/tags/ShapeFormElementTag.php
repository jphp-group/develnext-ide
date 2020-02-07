<?php
namespace ide\formats\form\tags;

use ide\formats\form\AbstractFormElementTag;
use php\gui\paint\UXColor;
use php\gui\shape\UXShape;
use php\gui\UXButton;
use php\gui\UXHyperlink;
use php\gui\UXSeparator;
use php\gui\UXSlider;
use php\xml\DomElement;

class ShapeFormElementTag extends AbstractFormElementTag
{
    public function getTagName()
    {
        return 'Shape';
    }

    public function isAbstract()
    {
        return true;
    }

    public function getElementClass()
    {
        return UXShape::class;
    }

    public function writeAttributes($node, DomElement $element)
    {
        /** @var UXShape $node */
        if (!$node->smooth) {
            $element->setAttribute('smooth', 'false');
        }

        if ($node->fill instanceof UXColor) {
            $element->setAttribute('fill', $node->fill->getWebValue());
        }

        if ($node->stroke instanceof UXColor) {
            $element->setAttribute('stroke', $node->stroke->getWebValue());
        }

        $element->setAttribute('strokeWidth', $node->strokeWidth);
        $element->setAttribute('strokeType', $node->strokeType);
    }
}