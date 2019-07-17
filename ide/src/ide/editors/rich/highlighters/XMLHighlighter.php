<?php

namespace ide\editors\rich\highlighters;

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
    public function highlight() {
        $regex = Regex::of(
            "(?<ELEMENT>(<\/?\\h*)([A-Za-z0-9_-]+)([^<>]*)(\\h*/?>))|(?<COMMENT><!--[^<>]+-->)",
            Regex::MULTILINE, $this->_text);

        while ($regex->find())
        {
            if ($regex->group("COMMENT"))
                $this->appendStyleClass($regex->start("COMMENT"), $regex->end("COMMENT"), "comment");
            elseif ($regex->group("ELEMENT")) {
                $this->appendStyleClass($regex->start(XMLHighlighter::GROUP_OPEN_BRACKET) + 1, $e = $regex->end(XMLHighlighter::GROUP_ELEMENT_NAME), "keyword");
                $attributesText = $regex->group(XMLHighlighter::GROUP_ATTRIBUTES_SECTION);

                if ($attributesText != null) {
                    $atr = Regex::of($this->ATTRIBUTES, Regex::MULTILINE, $attributesText);
                    while ($atr->find()) {
                        $this->appendStyleClass($e + $atr->start(XMLHighlighter::GROUP_ATTRIBUTE_NAME), $e + $atr->end(XMLHighlighter::GROUP_ATTRIBUTE_NAME), "variable");
                        $this->appendStyleClass($e + $atr->start(XMLHighlighter::GROUP_EQUAL_SYMBOL), $e + $atr->end(XMLHighlighter::GROUP_ATTRIBUTE_VALUE), "string");
                    }
                }
            }
        }
    }
}