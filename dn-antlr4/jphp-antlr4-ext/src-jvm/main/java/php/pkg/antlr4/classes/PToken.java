package php.pkg.antlr4.classes;

import org.antlr.v4.runtime.IntStream;
import org.antlr.v4.runtime.Token;
import php.pkg.antlr4.ANTLR4Extension;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

@Reflection.Name("Token")
@Reflection.Namespace(ANTLR4Extension.NS)
@Reflection.Abstract
public class PToken extends BaseWrapper<Token> {
    public PToken(Environment env, Token wrappedObject) {
        super(env, wrappedObject);
    }

    public PToken(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    public static final int INVALID_TYPE = 0;
    public static final int EPSILON = -2;
    public static final int MIN_USER_TOKEN_TYPE = 1;
    public static final int EOF = IntStream.EOF;
    public static final int DEFAULT_CHANNEL = 0;
    public static final int HIDDEN_CHANNEL = 1;
    public static final int MIN_USER_CHANNEL_VALUE = 2;

    interface WrappedInterface {
        String getText();
        int getType();
        int getLine();
        int getCharPositionInLine();
        int getChannel();
        int getTokenIndex();
        int getStartIndex();
        int getStopIndex();
    }
}
