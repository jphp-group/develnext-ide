<?php

namespace ide\editors\rich\highlighters;

use php\lib\str;
use php\util\Regex;

class YAMLHighlighter extends AbstractHighlighter {

    /**
     * Regex group to fx-css class
     *
     * @var array
     */
    private $classes = [
        "STRING" => "string",
        "STRINGALT" => "string",
        "COMMENT" => "comment",
        "NUMBER" => "number",
        "KEYWORD" => "keyword",
    ];

    /**
     * @throws \php\util\RegexException
     */
    public function highlight() {
        $regex = Regex::of(str::join([
            "(?<NUMBER>[-+]?[0-9]*\.?[0-9]+)",
            "|(?<STRING>\"(.)+\")",
            "|(?<STRINGALT>\'(.)+\')",
            "|(?<COMMENT>#(.)+$)",
            "|(?<KEYWORD>([A-Za-z0-9_-]+)\:)",
        ], null), Regex::MULTILINE, $this->_text);

        while ($regex->find())
        {
            $regex->group("NUMBER") != null ? $group = "NUMBER" :
                $regex->group("STRING") != null ? $group = "STRING" :
                    $regex->group("STRINGALT") != null ? $group = "STRINGALT" :
                        $regex->group("COMMENT") != null ? $group = "COMMENT" : null;
            $regex->group("KEYWORD") != null ? $group = "KEYWORD" : null;
            $this->appendStyleClass($regex->start($group), $regex->end($group), $this->classes[$group]);
        }
    }
}