<?php

namespace php\antlr4;

abstract class Token {
    public const INVALID_TYPE = 0;
    public const EPSILON = -2;
    public const MIN_USER_TOKEN_TYPE = 1;
    public const EOF = -1;
    public const DEFAULT_CHANNEL = 0;
    public const HIDDEN_CHANNEL = 1;
    public const MIN_USER_CHANNEL_VALUE = 2;

    /**
     * @return string
     */
    public function getText() : string {
        return "";
    }

    /**
     * @return int
     */
    public function getType() : int {
        return -1;
    }

    /**
     * @return int
     */
    public function getLine() : int {
        return -1;
    }

    /**
     * @return int
     */
    public function getCharPositionInLine() : int {
        return -1;
    }

    /**
     * @return int
     */
    public function getChannel() : int {
        return -1;
    }

    /**
     * @return int
     */
    public function getTokenIndex() : int {
        return -1;
    }

    /**
     * @return int
     */
    public function getStartIndex() : int {
        return 1;
    }

    /**
     * @return int
     */
    public function getStopIndex() : int {
        return -1;
    }
}