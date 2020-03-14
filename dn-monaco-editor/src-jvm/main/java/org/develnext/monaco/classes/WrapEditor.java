package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.Document;
import eu.mihosoft.monacofx.Editor;
import eu.mihosoft.monacofx.ViewController;
import eu.mihosoft.monacofx.model.Selection;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Property;
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
        @Property String currentTheme();
        @Property String currentLanguage();
        @Property boolean readOnly();
        @Property Document document();

        ViewController getViewController();

        boolean isInitialized();
        Selection getSelection();
        void setSelection(Selection range);

        void revealLine(int lineNumber);
        void revealLine(int lineNumber, int type);
        void revealLineInCenter(int lineNumber);
        void revealLineInCenter(int lineNumber, int type);
        void revealLineInCenterIfOutsideViewport(int lineNumber);
        void revealLineInCenterIfOutsideViewport(int lineNumber, int type);
    }
}
