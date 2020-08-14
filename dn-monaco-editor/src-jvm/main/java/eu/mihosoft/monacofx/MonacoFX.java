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
import javafx.application.Platform;
import javafx.concurrent.Worker;
import javafx.event.EventHandler;
import javafx.geometry.HPos;
import javafx.geometry.VPos;
import javafx.scene.control.ContextMenu;
import javafx.scene.input.Clipboard;
import javafx.scene.input.ClipboardContent;
import javafx.scene.input.KeyEvent;
import javafx.scene.input.MouseButton;
import javafx.scene.layout.Region;
import javafx.scene.web.WebEngine;
import javafx.scene.web.WebView;
import netscape.javascript.JSObject;
import org.develnext.jphp.ext.javafx.classes.UXClipboard;

import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicInteger;

public class MonacoFX extends Region {
    private final WebView view;
    private final WebEngine engine;
    private final Editor editor;
    private final JavaBridge bridge;
    private Runnable loaded;
    private boolean isLoaded = false;
    private ContextMenu contextMenu;

    private final static String EDITOR_HTML_RESOURCE_LOCATION = "/eu/mihosoft/monacofx/monaco-editor-0.20.0/index.html";

    public MonacoFX() {
        view = new WebView();
        view.setOpacity(0);
        view.setContextMenuEnabled(false);
        engine = view.getEngine();
        engine.load(getClass().getResource(EDITOR_HTML_RESOURCE_LOCATION).toExternalForm());
        editor = new Editor(engine);
        bridge = new JavaBridge();
        bridge.setEditor(editor);

        engine.getLoadWorker().stateProperty().addListener((o, old, state) -> {
            if (state == Worker.State.SUCCEEDED) {

                JSObject window = (JSObject) engine.executeScript("window");
                window.setMember("console", bridge);

                AtomicBoolean jsDone = new AtomicBoolean(false);
                AtomicInteger attempts = new AtomicInteger();

                Thread thread = new Thread(() -> {
                    while (!jsDone.get()) {
                        try {
                            Thread.sleep(500);
                        } catch (InterruptedException e) {
                            e.printStackTrace();
                        }
                        // check if JS execution is done.
                        Platform.runLater(() -> {
                            Object jsEditorObj = window.call("getEditorView");
                            if (jsEditorObj instanceof JSObject) {
                                editor.setEditor(window, (JSObject) jsEditorObj);
                                jsDone.set(true);
                                view.setOpacity(1);

                                if (loaded != null && !isLoaded) {
                                    loaded.run();
                                    isLoaded = true;
                                }
                            }
                        });

                        if(attempts.getAndIncrement()> 10) {
                            throw new RuntimeException(
                                "Cannot initialize editor (JS execution not complete). Max number of attempts reached."
                            );
                        }
                    }
                });
                thread.start();

            }
        });

        getChildren().add(view);
        view.setOnMouseClicked(mouseEvent -> {
            if (mouseEvent.getButton() == MouseButton.SECONDARY) {
                if (contextMenu != null && view.getOpacity() != 0) {
                    contextMenu.show(this, mouseEvent.getScreenX(), mouseEvent.getScreenY());
                }
            } else {
                contextMenu.hide();
            }
        });

        view.setOnKeyPressed(keyEvent -> {
            if (keyEvent.isControlDown() && keyEvent.getCharacter().equalsIgnoreCase("c")) {
                ClipboardContent content = new ClipboardContent();
                content.putString(getEditor().getDocument().getTextInRange(getEditor().getSelection()));
                Clipboard.getSystemClipboard().setContent(content);
            } else if (keyEvent.isControlDown() && keyEvent.getCharacter().equalsIgnoreCase("v")) {
                getEditor().getDocument().insert(Clipboard.getSystemClipboard().getString());
            }
        });
    }

    @Override protected double computePrefWidth(double height) {
        return view.prefWidth(height);
    }

    @Override protected double computePrefHeight(double width) {
        return view.prefHeight(width);
    }

    @Override public void requestLayout() {
        super.requestLayout();
    }

    @Override protected void layoutChildren() {
        super.layoutChildren();

        layoutInArea(view,0,0,getWidth(), getHeight(),
                0, HPos.CENTER, VPos.CENTER
        );
    }

    public Editor getEditor() {
        return editor;
    }

    @Override
    public void requestFocus() {
        super.requestFocus();
        getEditor().focus();
    }


    public void setLoaded(Runnable loaded) {
        this.loaded = loaded;
    }

    public ContextMenu getContextMenu() {
        return contextMenu;
    }

    public void setContextMenu(ContextMenu contextMenu) {
        this.contextMenu = contextMenu;
    }

    public static class JavaBridge {
        private Editor editor;

        public Editor getEditor() {
            return editor;
        }

        public void setEditor(Editor editor) {
            this.editor = editor;
        }

        public void log(String log) {
            System.out.println(log);
        }

        public void error(String error) {
            System.err.println(error);
        }

        public String executeJavaCallback(String id, String data) {
            return new Gson().toJson(getEditor().executeCallback(id, data));
        }
    }
}
