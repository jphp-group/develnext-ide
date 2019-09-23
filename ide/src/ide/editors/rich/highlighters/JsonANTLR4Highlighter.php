<?php

namespace ide\editors\rich\highlighters;

class JsonANTLR4Highlighter extends AbstractANTLR4Highlighter {

    /**
     * @return string
     */
    protected function getALTLR4Class(): string {
        return "JSONLexer";
    }

    /**
     * @param int $type
     * @return string
     */
    protected function getStyleClassByType(int $type): string {
        switch ($type) {
            case 10: return "string";
            case 11: return "number";

            case 7:
            case 8:
            case 9: return "keyword";

            default:
                return "text";
        }
    }
}