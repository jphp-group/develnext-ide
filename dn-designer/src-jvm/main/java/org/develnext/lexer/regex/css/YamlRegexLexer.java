package org.develnext.lexer.regex.css;

import org.develnext.lexer.regex.RegexLexer;
import org.develnext.lexer.regex.RegexLexerRule;

public class YamlRegexLexer extends RegexLexer {
    public YamlRegexLexer() {
        super();

        addRule(new RegexLexerRule("BRACKET", "\\[|\\]"));
        addRule(new RegexLexerRule("CONTROL", "\\-|\\,|\\^|\\~"));
        addRule(new RegexLexerRule("STRING", "((\"([^\"\\\\]|\\\\.)*\")|(\'([^\'\\\\]|\\\\.)*\'))"));
        addRule(new RegexLexerRule("COMMENT", "(\\#(.)+)$"));
        addRule(new RegexLexerRule("SELECTOR", "[a-zA-Z\\_\\-]{1}[a-zA-Z0-9\\_\\-]{0,}[ ]{0,}\\:"));

        addRule(new RegexLexerRule("NUMBER", "[0-9]+(\\.[0-9]+)?"));
        addRule(new RegexLexerRule("COLOR", "\\#[\\dA-Fa-f]{2,6}"));
    }
}
