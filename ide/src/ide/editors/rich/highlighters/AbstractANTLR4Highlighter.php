<?php


namespace ide\editors\rich\highlighters;


use php\antlr4\Lexer;
use php\gui\UXStyleSpansBuilder;
use php\lib\str;

abstract class AbstractANTLR4Highlighter extends AbstractHighlighter {

    /**
     * @param UXStyleSpansBuilder $builder
     * @throws \php\lang\JavaException
     */
    public function highlight(UXStyleSpansBuilder $builder) {
        if (str::length($this->_text) > 0) {
            $lexer = Lexer::get($this->getALTLR4Class(), $this->_text);
            $tokens = $lexer->getAllTokens();

            $lastEnd = 0;

            $builder->add([], 1);
            foreach ($tokens as $token) {
                $builder->add([ $this->getStyleClassByType($token->getType()) ],
                    $token->getStopIndex() - $lastEnd);

                $lastEnd = $token->getStopIndex();
            }
        }
    }

    /**
     * @return string
     */
    abstract protected function getALTLR4Class(): string;

    /**
     * @param int $type
     * @return string
     */
    abstract protected function getStyleClassByType(int $type): string;
}