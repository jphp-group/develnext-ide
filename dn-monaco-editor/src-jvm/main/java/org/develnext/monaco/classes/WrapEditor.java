package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.Document;
import eu.mihosoft.monacofx.Editor;
import eu.mihosoft.monacofx.ViewController;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

@Reflection.Abstract
@Reflection.Name("Editor")
@Reflection.Namespace(MonacoEditorExtension.NS)
public class WrapEditor extends BaseWrapper<Editor> {
    public WrapEditor(Environment env, Editor wrappedObject) {
        super(env, wrappedObject);
    }

    public WrapEditor(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    interface WrappedInterface {
        @Reflection.Property String currentTheme();
        @Reflection.Property String currentLanguage();
        @Reflection.Property Document document();

        ViewController getViewController();
    }
}
