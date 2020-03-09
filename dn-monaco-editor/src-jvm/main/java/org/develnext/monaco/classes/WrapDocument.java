package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.Document;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.invoke.Invoker;
import php.runtime.lang.BaseWrapper;
import php.runtime.memory.StringMemory;
import php.runtime.reflection.ClassEntity;

@Reflection.Abstract
@Reflection.Name("Document")
@Reflection.Namespace(MonacoEditorExtension.NS)
public class WrapDocument extends BaseWrapper<Document> {
    public WrapDocument(Environment env, Document wrappedObject) {
        super(env, wrappedObject);
    }

    public WrapDocument(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Reflection.Getter
    public Integer getNumberOfLines() {
        return getWrappedObject().numberOfLinesProperty().get();
    }

    @Reflection.Signature
    public void addTextChangeListener(Invoker callback) {
        getWrappedObject().textProperty().addListener((observableValue, oldValue, newValue)
                -> callback.callNoThrow(StringMemory.valueOf(oldValue), StringMemory.valueOf(newValue)));
    }

    interface WrappedInterface {
        @Reflection.Property String text();
    }
}
