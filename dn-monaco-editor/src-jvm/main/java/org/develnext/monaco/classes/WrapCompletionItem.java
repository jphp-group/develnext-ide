package org.develnext.monaco.classes;

import eu.mihosoft.monacofx.CompletionItemProvider;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

@Reflection.Name("CompletionItem")
@Reflection.Namespace(MonacoEditorExtension.NS)
public class WrapCompletionItem extends BaseWrapper<CompletionItemProvider.CompletionItem> {
    public WrapCompletionItem(Environment env, CompletionItemProvider.CompletionItem wrappedObject) {
        super(env, wrappedObject);
    }

    public WrapCompletionItem(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Reflection.Signature
    public void __construct() {
        __wrappedObject = new CompletionItemProvider.CompletionItem();
    }

    @Reflection.Getter
    public boolean getInsertAsSnippet() {
        return getWrappedObject().getInsertTextRules() == 4;
    }

    @Reflection.Setter
    public void setInsertAsSnippet(boolean asSnippet) {
        getWrappedObject().setInsertTextRules(asSnippet ? 4 : 1);
    }

    interface WrappedInterface {
        @Reflection.Property String label();
        @Reflection.Property Integer kind();
        @Reflection.Property String documentation();
        @Reflection.Property String insertText();
    }
}
