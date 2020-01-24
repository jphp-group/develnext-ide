package org.develnext.jphp.gui.designer.classes;

import javafx.embed.swing.JFXPanel;
import javafx.embed.swing.SwingNode;
import javafx.scene.Parent;
import javafx.scene.Scene;
import org.develnext.jphp.ext.javafx.classes.UXNode;
import org.develnext.jphp.gui.designer.GuiDesignerExtension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Namespace(GuiDesignerExtension.NS)
public class UXIsolatedNode extends UXNode<SwingNode> {
    public UXIsolatedNode(Environment env, SwingNode wrappedObject) {
        super(env, wrappedObject);
    }

    public UXIsolatedNode(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Reflection.Signature
    public void __construct(Parent node) {
        JFXPanel panel = new JFXPanel();
        panel.setScene(new Scene(node));
        SwingNode swingNode = new SwingNode();
        swingNode.setContent(panel);
        __wrappedObject = swingNode;
    }
}
