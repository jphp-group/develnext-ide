package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.ViewController;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

@Reflection.Name("ViewController")
@Reflection.Namespace(MonacoEditorExtension.NS)
public class WrapViewController extends BaseWrapper<ViewController> {
    public WrapViewController(Environment env, ViewController wrappedObject) {
        super(env, wrappedObject);
    }

    public WrapViewController(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    interface WrappedInterface {
        @Reflection.Property int scrollPosition();

        void scrollToLine(int line);
        void scrollToLineCenter(int line);
    }
}
