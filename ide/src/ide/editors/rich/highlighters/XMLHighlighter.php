<?php

namespace ide\editors\rich\highlighters;

use php\gui\UXStyleSpansBuilder;
use php\lib\str;
use php\util\Regex;

class XMLHighlighter extends AbstractHighlighter {

    private $ATTRIBUTES = '(\\w+\\h*)(=)(\\h*\"[^\"]+\")';

    const GROUP_OPEN_BRACKET = 2;
    const GROUP_ELEMENT_NAME = 3;
    const GROUP_ATTRIBUTES_SECTION = 4;
    const GROUP_CLOSE_BRACKET = 5;
    const GROUP_ATTRIBUTE_NAME = 1;
    const GROUP_EQUAL_SYMBOL = 2;
    const GROUP_ATTRIBUTE_VALUE = 3;

    /**
     * @throws \php\util\RegexException
     */
    public function highlight(UXStyleSpansBuilder $builder) {
        $regex = Regex::of(
            "(?<ELEMENT>(<\/?\\h*)([A-Za-z0-9_-]+)([^<>]*)(\\h*/?>))|(?<COMMENT><!--[^<>]+-->)",
            Regex::MULTILINE, $this->_text);

        $lastKwEnd = 0;
        while ($regex->find())
        {
            $builder->add([], $regex->start() - $lastKwEnd);

            if ($regex->group("COMMENT"))
                $builder->add(["comment"], $regex->end("COMMENT") - $regex->start("COMMENT"));
            elseif ($regex->group("ELEMENT")) {
                $attributesText = $regex->group(XMLHighlighter::GROUP_ATTRIBUTES_SECTION);

                $builder->add([], $regex->end(XMLHighlighter::GROUP_OPEN_BRACKET)
                    - $regex->start(XMLHighlighter::GROUP_OPEN_BRACKET));
                $builder->add(["keyword"], $regex->end(XMLHighlighter::GROUP_ELEMENT_NAME)
                    - $regex->end(XMLHighlighter::GROUP_OPEN_BRACKET));

                if ($attributesText != null) {
                    $atr = Regex::of($this->ATTRIBUTES, Regex::MULTILINE, $attributesText);
                    $lastKwEnd = 0;

                    while ($atr->find()) {
                        $builder->add([], $atr->start() - $lastKwEnd);
                        $builder->add(["variable"],
                            $atr->end(XMLHighlighter::GROUP_ATTRIBUTE_NAME)
                            - $atr->start(XMLHighlighter::GROUP_ATTRIBUTE_NAME));
                        $builder->add([], $atr->end(XMLHighlighter::GROUP_EQUAL_SYMBOL)
                            - $atr->end(XMLHighlighter::GROUP_ATTRIBUTE_NAME));
                        $builder->add(["string"], $atr->end(XMLHighlighter::GROUP_ATTRIBUTE_VALUE)
                            - $atr->end(XMLHighlighter::GROUP_EQUAL_SYMBOL));

                        $lastKwEnd = $atr->end();
                    }

                    if (str::length($attributesText) > $lastKwEnd)
                        $builder->add([], str::length($attributesText) - $lastKwEnd);
                }

                $lastKwEnd = $regex->end(XMLHighlighter::GROUP_ATTRIBUTES_SECTION);
                $builder->add([], $regex->end(XMLHighlighter::GROUP_CLOSE_BRACKET) - $lastKwEnd);
            }

            $lastKwEnd = $regex->end();
        }

        $builder->add([], str::length($this->_text) - $lastKwEnd);
    }
}