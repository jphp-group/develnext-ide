package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.Editor;
import eu.mihosoft.monacofx.MonacoFX;
import org.develnext.jphp.ext.javafx.classes.layout.UXRegion;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Name("MonacoEditor")
@Reflection.Namespace(MonacoEditorExtension.NS)
public class WrapMonacoEditor extends UXRegion<MonacoFX> {
    public WrapMonacoEditor(Environment env, MonacoFX wrappedObject) {
        super(env, wrappedObject);
    }

    public WrapMonacoEditor(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Reflection.Signature
    public void __construct() {
        __wrappedObject = new MonacoFX();
    }

    @Reflection.Signature
    public Editor getEditor() {
        return getWrappedObject().getEditor();
    }
}
