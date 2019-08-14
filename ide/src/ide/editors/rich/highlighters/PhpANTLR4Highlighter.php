<?php

namespace ide\editors\rich\highlighters;

class PhpANTLR4Highlighter extends AbstractANTLR4Highlighter {

    /**
     * @return string
     */
    protected function getALTLR4Class(): string {
        return "PhpLexer";
    }

    /**
     * @param int $type
     * @return string
     */
    protected function getStyleClassByType(int $type): string {
        // comments
        if ($type >= 40 && $type <= 42 || $type == 227)
            return "comment";

        // keywords
        if ($type >= 43 && $type <= 127)
            return "keyword";

        // magic-functions
        if ($type >= 128 && $type <= 151)
            return "magic-function";

        // operators
        if ($type >= 152 && $type <= 211)
            return "operator";

        // variables
        if ($type == 212)
            return "variable";

        // numbers
        if ($type >= 214 && $type <= 218)
            return "number";

        // strings
        if ($type >= 219 && $type <= 220 || $type == 226)
            return "string";

        return "text";
    }
}