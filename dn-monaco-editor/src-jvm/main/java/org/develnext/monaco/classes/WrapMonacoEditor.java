package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.Editor;
import eu.mihosoft.monacofx.MonacoFX;
import javafx.scene.control.ContextMenu;
import org.develnext.jphp.ext.javafx.classes.layout.UXRegion;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.invoke.Invoker;
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
    public void __construct(String htmlSource) {
        __wrappedObject = new MonacoFX(htmlSource);
    }

    @Reflection.Signature
    public Editor getEditor() {
        return getWrappedObject().getEditor();
    }

    @Reflection.Signature
    public void setOnLoad(Invoker callback) {
        getWrappedObject().setLoaded(callback::callNoThrow);
    }

    @Reflection.Getter
    public ContextMenu getContextMenu() {
        return getWrappedObject().getContextMenu();
    }

    @Reflection.Setter
    public void setContextMenu(ContextMenu contextMenu) {
        getWrappedObject().setContextMenu(contextMenu);
    }
}
