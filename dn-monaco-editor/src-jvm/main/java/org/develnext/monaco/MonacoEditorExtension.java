package org.develnext.monaco;

import eu.mihosoft.monacofx.*;
import org.develnext.monaco.classes.*;
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
        registerWrapperClass(scope, CompletionItemProvider.CompletionItem.class, WrapCompletionItem.class);
    }
}