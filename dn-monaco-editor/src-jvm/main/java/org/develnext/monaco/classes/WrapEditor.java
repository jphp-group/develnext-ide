package org.develnext.monaco.classes;

import com.google.gson.Gson;
import eu.mihosoft.monacofx.CompletionItemProvider;
import eu.mihosoft.monacofx.CompletionItemProvider.CompletionItem;
import eu.mihosoft.monacofx.Document;
import eu.mihosoft.monacofx.Editor;
import eu.mihosoft.monacofx.ViewController;
import eu.mihosoft.monacofx.model.Position;
import eu.mihosoft.monacofx.model.Selection;
import java.util.Collections;
import org.develnext.jphp.json.classes.JsonProcessor;
import org.develnext.monaco.MonacoEditorExtension;
import php.runtime.Memory;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Property;
import php.runtime.env.Environment;
import php.runtime.ext.core.classes.lib.StrUtils;
import php.runtime.invoke.Invoker;
import php.runtime.lang.BaseWrapper;
import php.runtime.memory.LongMemory;
import php.runtime.memory.StringMemory;
import php.runtime.reflection.ClassEntity;

import java.util.ArrayList;
import java.util.List;

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

    @Reflection.Signature
    public void registerCompletionItemProvider(String language, String triggerCharacters,
                                                Invoker callback, Invoker resolveCallback) {
        CompletionItemProvider completionItemProvider = new CompletionItemProvider() {
            @Override
            public List<CompletionItem> complete(RangeWithPosition rangeWithPosition) {
                List<CompletionItem> items = new ArrayList<>();
                try {
                    Memory memory = callback.call(StrUtils.parseAs(getEnvironment(),
                        new StringMemory(new Gson().toJson(rangeWithPosition)),
                        new StringMemory("json"),
                        new LongMemory(1024)));

                    for (Memory iter : memory.getNewIterator(getEnvironment()))
                        items.add(iter.toObject(WrapCompletionItem.class).getWrappedObject());
                } catch (Throwable e) {
                    e.printStackTrace();
                }

                return items;
            }

            @Override
            public CompletionItem resolve(RangeWithPositionAndItem rangeWithPositionAndItem) {
                try {
                    Memory memory = resolveCallback.call(StrUtils.parseAs(getEnvironment(),
                        new StringMemory(new Gson().toJson(rangeWithPositionAndItem)),
                        new StringMemory("json"),
                        new LongMemory(1024)));
                    if (memory.isNull()) return null;
                    return memory.toObject(WrapCompletionItem.class).getWrappedObject();
                } catch (Throwable throwable) {
                    throwable.printStackTrace();
                    return null;
                }
            }
        };

        getWrappedObject().registerCompletionItemProvider(language, triggerCharacters, completionItemProvider);
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
        Position getPosition();
        Long getPositionOffset();
        void setPosition(Position position);
        void revealLine(int lineNumber);
        void revealLine(int lineNumber, int type);
        void revealLineInCenter(int lineNumber);
        void revealLineInCenter(int lineNumber, int type);
        void revealLineInCenterIfOutsideViewport(int lineNumber);
        void revealLineInCenterIfOutsideViewport(int lineNumber, int type);

        void trigger(String action);
        boolean copy();
        boolean cut();
        boolean paste();
        void undo();
        void redo();
    }
}
