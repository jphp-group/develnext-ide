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
import eu.mihosoft.monacofx.model.Selection;
import java.util.List;
import javafx.application.Platform;
import javafx.beans.property.*;
import javafx.collections.FXCollections;
import javafx.collections.ListChangeListener;
import javafx.collections.ObservableList;
import javafx.scene.web.WebEngine;
import netscape.javascript.JSObject;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.UUID;
import org.develnext.jphp.ext.javafx.classes.UXClipboard;

public final class Editor {

    private final WebEngine engine;
    private JSObject window;
    private JSObject editor;
    private final ViewController viewController;
    private final ObjectProperty<Document> documentProperty = new SimpleObjectProperty<>();
    private final ObservableList<LanguageSupport> languages = FXCollections.observableArrayList();
    private final ObservableList<EditorTheme> themes = FXCollections.observableArrayList();

    private final StringProperty currentThemeProperty = new SimpleStringProperty();
    private final StringProperty currentLanguageProperty = new SimpleStringProperty();
    private final BooleanProperty readOnlyProperty = new SimpleBooleanProperty();
    private final Map<String, JSCallback> callbackMap = new LinkedHashMap<String, JSCallback>();

    Editor(WebEngine engine) {
        this.engine = engine;
        this.viewController = new ViewController(this);
        Document document = new Document();
        setDocument(document);
    }

    JSObject getJSEditor() {
        return editor;
    }

    JSObject getJSWindow() {
        return window;
    }

    WebEngine getEngine() {
        return engine;
    }

    private void registerLanguageJS(LanguageSupport l) {

        String registerScript = "require(['vs/editor/editor.main'], function() {\n";

        String registerLang = "monaco.languages.register({ id: '"+ l.getName() + "' })\n";

        registerScript+=registerLang;

        if(l.getMonarchSyntaxHighlighter()!=null) {
            String registerMonarch = "monaco.languages.setMonarchTokensProvider(\"" + l.getName() + "\", {\n"
                    + l.getMonarchSyntaxHighlighter().getRules()
                    + "})\n";
            registerScript+=registerMonarch;
        }

        if(l.getFoldingProvider()!=null) {
            window.setMember(("foldingProvider_" + l.getName()),
                    new JFunction((args) -> l.getFoldingProvider().computeFoldings(this))
            );


            String registerFoldingProvider = "monaco.languages.registerFoldingRangeProvider('mylang', {\n"
                    + "         provideFoldingRanges: function(model, context, token) {\n"
                    + "     return foldingProvider_" + l.getName() + ".apply([model,context,token]);\n"
                    + "}\n"
                    + "});\n";

            registerScript+=registerFoldingProvider;
        }

        registerScript+="\n})";

        engine.executeScript(registerScript);
    }

    private void registerThemeJS(EditorTheme t) {
        String script = "monaco.editor.defineTheme('"+t.name+"', " + t.toJS()+")";
        engine.executeScript(script);
    }

    void setEditor(JSObject window, JSObject editor) {
        this.editor = editor;
        this.window = window;

        // register custom languages
        languages.forEach(this::registerLanguageJS);
        languages.addListener((ListChangeListener<LanguageSupport>) c -> {
            while(c.next()) {
                c.getAddedSubList().forEach(this::registerLanguageJS);
            }
        });

        // register custom themes
        themes.forEach(this::registerThemeJS);
        themes.addListener((ListChangeListener<EditorTheme>) c -> {
            while(c.next()) {
                c.getAddedSubList().forEach(this::registerThemeJS);
            }
        });

        // initial theme
        if(getCurrentTheme()!=null) {
            engine.executeScript("monaco.editor.setTheme('"+getCurrentTheme()+"')");
        }

        // theme changes -> js
        currentThemeProperty().addListener((ov) -> {
            engine.executeScript("monaco.editor.setTheme('"+getCurrentTheme()+"')");
        });

        editor.eval("this.updateOptions({readOnly:"+isReadOnly()+"})");
        readOnlyPropertyProperty().addListener((ov) -> {
            editor.eval("this.updateOptions({readOnly:"+isReadOnly()+"})");
        });

        // initial lang
        if(getCurrentLanguage()!=null) {
            engine.executeScript("monaco.editor.setModelLanguage(editorView.getModel(),'"+getCurrentLanguage()+"')");
        }

        // lang changes -> js
        currentLanguageProperty().addListener((ov) -> {
            engine.executeScript("monaco.editor.setModelLanguage(editorView.getModel(),'"+getCurrentLanguage()+"')");
        });

        getDocument().setEditor(engine, window, this);

        getViewController().setEditor(window, editor);
    }

    public boolean isInitialized() {
        return editor != null && engine != null;
    }

    public Object callEditorMethod(String method, Object... args) {
        if (isInitialized()) {
            return editor.call(method, args);
        } else {
            return null;
        }
    }

    public Selection getSelection() {
        JSObject selection = (JSObject) callEditorMethod("getSelection");
        return selection == null ? null : new Selection(selection);
    }

    public void setSelection(Selection range) {
        callEditorMethod("setSelection", getJSEditor().eval(range.toString()));
    }

    public Position getPosition() {
        return new Gson().fromJson(window.call("getPosition").toString(), Position.class);
    }

    public Long getPositionOffset() {
        return ((Number)window.call("getPositionOffset")).longValue();
    }

    public void setPosition(Position position) {
        window.call("setPosition", new Gson().toJson(position));
    }

    public boolean isReadOnly() {
        return readOnlyProperty.get();
    }

    public BooleanProperty readOnlyPropertyProperty() {
        return readOnlyProperty;
    }

    public void setReadOnly(boolean readOnlyProperty) {
        this.readOnlyProperty.set(readOnlyProperty);
    }

    public StringProperty currentThemeProperty() {
        return this.currentThemeProperty;
    }

    public void setCurrentTheme(String theme) {
        currentThemeProperty().set(theme);
    }

    public String getCurrentTheme() {
        return currentThemeProperty().get();
    }

    public StringProperty currentLanguageProperty() {
        return this.currentLanguageProperty;
    }

    public void setCurrentLanguage(String theme) {
        currentLanguageProperty().set(theme);
    }

    public String getCurrentLanguage() {
        return currentLanguageProperty().get();
    }

    public ObjectProperty<Document> documentProperty() {
        return documentProperty;
    }

    public void setDocument(Document document) {
        documentProperty().set(document);
    }

    public Document getDocument() {
        return documentProperty().get();
    }

    public ViewController getViewController() {
        return viewController;
    }

//    List<LanguageSupport> getLanguages() {
//        return languages;
//    }

    public void registerLanguage(LanguageSupport language) {
        this.languages.add(language);
    }

    public void registerTheme(EditorTheme theme) {
        this.themes.add(theme);
    }

    public void focus() {
        callEditorMethod("focus");
    }

    public void trigger(String action) {
        window.call("trigger", action);
    }

    public void undo() {
        window.call("undo");
    }

    public void redo() {
        window.call("redo");
    }

    public boolean cut() {
        if (copy()) {
            getDocument().insert("");
            return true;
        }

        return false;
    }

    public boolean copy() {
        String textInRange = getDocument().getTextInRange(getSelection());

        if (textInRange != null && !textInRange.isEmpty()) {
            Platform.runLater(() -> {
                UXClipboard.setText(textInRange);
            });
            return true;
        }

        return false;
    }

    public boolean paste() {
        String text = UXClipboard.getText();
        if (text != null && !text.isEmpty()) {
            getDocument().insert(text);
            return true;
        }

        return false;
    }

    public void revealLine(int lineNumber) {
        revealLine(lineNumber, 0);
    }

    public void revealLine(int lineNumber, int type) {
        callEditorMethod("revealLine", lineNumber, type);
    }

    public void revealLineInCenter(int lineNumber) {
        revealLineInCenter(lineNumber, 0);
    }

    public void revealLineInCenter(int lineNumber, int type) {
        callEditorMethod("revealLineInCenter", lineNumber, type);
    }

    public void revealLineInCenterIfOutsideViewport(int lineNumber) {
        revealLineInCenterIfOutsideViewport(lineNumber, 0);
    }

    public void revealLineInCenterIfOutsideViewport(int lineNumber, int type) {
        callEditorMethod("revealLineInCenterIfOutsideViewport", lineNumber, type);
    }

    public void registerCompletionItemProvider(String language, List<String> triggerCharacters, CompletionItemProvider itemProvider) {
        String id = UUID.randomUUID().toString();
        callbackMap.put(id, json -> itemProvider.complete(new Gson().fromJson(json, CompletionItemProvider.RangeWithPosition.class)));
        window.call("registerCompletionItemProvider", language, triggerCharacters, id);
    }

    public Object executeCallback(String id, String json) {
        JSCallback callback = callbackMap.get(id);

        if (callback != null)
            return callback.execute(json);

        return new Object();
    }

    public interface JSCallback {
        Object execute(String json);
    }
}
