<?php

namespace php\antlr4;

use php\lang\JavaException;

abstract class Parser {

    /**
     * @param string $javaClass
     * @param Lexer $lexer
     * @return Parser
     * @throws JavaException
     */
    public static function get(string $javaClass, Lexer $lexer) : Parser {
        return null;
    }

    /**
     * @param callable $callback (mixed offendingSymbol, int line, intcharPositionInLine, string msg)
     */
    public function addErrorListener(callable $callback) {

    }

    public function removeErrorListeners() {

    }

    /**
     * @param array $methods
     */
    public function start(array $methods) {

    }
}