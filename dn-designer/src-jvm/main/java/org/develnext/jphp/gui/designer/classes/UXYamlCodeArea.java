package org.develnext.jphp.gui.designer.classes;

import org.develnext.jphp.gui.designer.GuiDesignerExtension;
import org.develnext.jphp.gui.designer.editor.syntax.impl.FxCssCodeArea;
import org.develnext.jphp.gui.designer.editor.syntax.impl.YamlCodeArea;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Signature;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Namespace(GuiDesignerExtension.NS)
public class UXYamlCodeArea extends UXCssCodeArea<FxCssCodeArea> {
    interface WrappedInterface {
    }

    public UXYamlCodeArea(Environment env, FxCssCodeArea wrappedObject) {
        super(env, wrappedObject);
    }

    public UXYamlCodeArea(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Signature
    public void __construct() {
        __wrappedObject = new YamlCodeArea();
    }
}
