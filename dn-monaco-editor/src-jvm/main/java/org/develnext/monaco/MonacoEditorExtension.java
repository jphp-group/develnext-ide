package org.develnext.monaco;

import eu.mihosoft.monacofx.Document;
import eu.mihosoft.monacofx.Editor;
import eu.mihosoft.monacofx.MonacoFX;
import eu.mihosoft.monacofx.ViewController;
import org.develnext.monaco.classes.WrapDocument;
import org.develnext.monaco.classes.WrapEditor;
import org.develnext.monaco.classes.WrapMonacoEditor;
import org.develnext.monaco.classes.WrapViewController;
import php.runtime.env.CompileScope;
import php.runtime.ext.support.Extension;

public class MonacoEditorExtension extends Extension {
    public static final String NS = "php\\gui\\monaco";
    
    @Override
    public Status getStatus() {
        return Status.EXPERIMENTAL;
    }
    
    @Override
    public void onRegister(CompileScope scope) {
        registerWrapperClass(scope, MonacoFX.class, WrapMonacoEditor.class);
        registerWrapperClass(scope, Editor.class, WrapEditor.class);
        registerWrapperClass(scope, Document.class, WrapDocument.class);
        registerWrapperClass(scope, ViewController.class, WrapViewController.class);
    }
}