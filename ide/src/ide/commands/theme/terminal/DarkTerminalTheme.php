<?php


namespace ide\commands\theme\terminal;

class DarkTerminalTheme extends TerminalTheme {
    public function getDefaultStyle(): TextStyle {
        return new TextStyle("white", "#393939");
    }
}