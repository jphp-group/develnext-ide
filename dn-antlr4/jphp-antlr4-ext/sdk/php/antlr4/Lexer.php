<?php

namespace php\antlr4;

use php\lang\JavaException;

abstract class Lexer {

    /**
     * @param string $javaClass
     * @param string $inputString
     * @return Lexer
     * @throws JavaException
     */
    public static function get(string $javaClass, string $inputString) : Lexer {
        return null;
    }

    /**
     * @return Token[]
     */
    public function getAllTokens() : array {
        return [];
    }
}