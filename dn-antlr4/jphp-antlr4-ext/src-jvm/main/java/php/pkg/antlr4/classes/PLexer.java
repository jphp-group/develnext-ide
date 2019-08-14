package php.pkg.antlr4.classes;

import org.antlr.v4.runtime.CharStream;
import org.antlr.v4.runtime.CharStreams;
import org.antlr.v4.runtime.Lexer;
import org.antlr.v4.runtime.Token;
import php.pkg.antlr4.ANTLR4Extension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

import java.lang.reflect.InvocationTargetException;
import java.util.List;

@Reflection.Name("Lexer")
@Reflection.Namespace(ANTLR4Extension.NS)
@Reflection.Abstract
public class PLexer extends BaseWrapper<Lexer> {
    public PLexer(Environment env, Lexer wrappedObject) {
        super(env, wrappedObject);
    }

    public PLexer(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Reflection.Signature
    public static PLexer get(Environment __env__, String clazz, String input)
            throws ClassNotFoundException, NoSuchMethodException, IllegalAccessException,
                InvocationTargetException, InstantiationException {
        return new PLexer(__env__, (Lexer) Class.forName(clazz)
                .getConstructor(CharStream.class)
                .newInstance(CharStreams.fromString(input)));
    }

    @Reflection.Signature
    public List<Token> getAllTokens() {
        return (List<Token>) getWrappedObject().getAllTokens();
    }
}
