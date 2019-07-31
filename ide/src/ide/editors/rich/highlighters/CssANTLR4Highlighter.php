<?php


namespace ide\editors\rich\highlighters;

class CssANTLR4Highlighter extends AbstractANTLR4Highlighter {

    /**
     * @return string
     */
    protected function getALTLR4Class(): string {
        return "css3Lexer";
    }

    /**
     * @param int $type
     * @return string
     */
    protected function getStyleClassByType(int $type): string {
        if ($type == 16)
            return "comment";

        if ($type == 43)
            return "number";

        if ($type == 30 || $type == 44 || $type == 22)
            return "string";



        if ($type >= 1 && $type <= 21 ||
            $type >= 37 && $type <= 47)
            return "operator";

        if ($type >= 48 && $type <= 61)
            return "magic-function";

        return "text";
    }
}