/*
 * MIT License
 *
 * Copyright (c) 2020 Michael Hoffer <info@michaelhoffer.de>. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
package eu.mihosoft.monacofx;

import com.google.gson.Gson;
import eu.mihosoft.monacofx.model.Position;
import eu.mihosoft.monacofx.model.Range;
import javafx.beans.property.*;
import javafx.scene.web.WebEngine;
import netscape.javascript.JSObject;

public class Document {

    private WebEngine engine;
    private Editor editor;
    private JSObject editorGlobal;
    private JSObject window;
    private JFunction contentChangeListener;

    private final StringProperty textProperty = new SimpleStringProperty();
    private final IntegerProperty numberOfLinesProperty = new SimpleIntegerProperty();

    void setEditor(WebEngine engine, JSObject window, Editor editor) {
        this.engine = engine;
        this.editor = editor;
        this.window = window;
        this.contentChangeListener = new JFunction(args -> {
            String text = (String) editor.getJSEditor().call("getValue");
            if (text != null) {
                setText(text);
                numberOfLinesProperty.setValue(text.split("\\R").length);
            }

            return null;
        });

        // initial text
        editor.getJSEditor().call("setValue", getText());

        // text changes -> js
        textProperty.addListener((ov) -> {
            editor.getJSEditor().call("setValue", getText());
        });

        // text changes <- js
        window.setMember("contentChangeListener", contentChangeListener);
    }

    public StringProperty textProperty() {
        return textProperty;
    }

    public void setText(String text) {
        textProperty().set(text);
    }

    public String getText() {
        return textProperty().get();
    }

    public ReadOnlyIntegerProperty numberOfLinesProperty() {
        return numberOfLinesProperty;
    }

    public String getTextInRange(Range range) {
        return (String) window.call("getTextInRange", new Gson().toJson(range));
    }

    protected static class RangeWithText {
        private Range range;
        private String text;

        public RangeWithText(Range range, String text) {
            this.range = range;
            this.text = text;
        }
    }

    protected boolean executeEdits(Range rage, String text) {
        return (boolean)
                window.call("executeEdits",
                        new Gson().toJson(
                                new RangeWithText(rage, text)));
    }

    public void insert(String text) {
        insert(text, true);
    }

    public void insert(String text, boolean replaceIfSelected) {
        Range range = null;
        Position line = editor.getPosition();

        if (replaceIfSelected) {
            range = editor.getSelection();
        } else {
            range = new Range(line.getLineNumber(), line.getColumn(), line.getLineNumber(), line.getColumn());
        }

        executeEdits(range, text);
        editor.setPosition(new Position(line.getColumn() + text.length(), line.getLineNumber()));
    }
}
