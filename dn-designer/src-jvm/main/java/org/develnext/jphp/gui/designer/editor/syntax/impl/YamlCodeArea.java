package org.develnext.jphp.gui.designer.editor.syntax.impl;


import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;
import org.develnext.jphp.gui.designer.editor.syntax.hotkey.*;
import org.develnext.lexer.regex.RegexToken;
import org.develnext.lexer.regex.css.YamlRegexLexer;
import org.fxmisc.richtext.model.StyleSpansBuilder;

import java.util.Collection;
import java.util.Collections;

public class YamlCodeArea extends AbstractCodeArea {
    protected final YamlRegexLexer yamlRegexLexer = new YamlRegexLexer();

    public YamlCodeArea() {
        super();

        registerHotkey(new AddTabsHotkey());
        registerHotkey(new RemoveTabsHotkey());
        registerHotkey(new DuplicateSelectionHotkey());
        registerHotkey(new AutoSpaceEnterHotkey());
        registerHotkey(new AutoBracketsHotkey());
        registerHotkey(new BackspaceHotkey());

        setStylesheet(AbstractCodeArea.class.getResource("CssCodeArea.css").toExternalForm());
    }

    @Override
    protected void computeHighlighting(StyleSpansBuilder<Collection<String>> spansBuilder, String text) {
        yamlRegexLexer.setContent(text);

        int lastKwEnd = 0;
        RegexToken token;

        while ((token = yamlRegexLexer.nextToken()) != null) {
            spansBuilder.add(Collections.emptyList(), token.getStart() - lastKwEnd);
            spansBuilder.add(Collections.singleton(token.getCode().toLowerCase()), token.getEnd() - token.getStart());
            lastKwEnd = token.getEnd();
        }

        spansBuilder.add(Collections.emptyList(), text.length() - lastKwEnd);
    }
}