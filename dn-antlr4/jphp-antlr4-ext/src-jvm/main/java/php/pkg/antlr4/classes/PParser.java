package php.pkg.antlr4.classes;

import org.antlr.v4.runtime.*;
import org.antlr.v4.runtime.atn.ATNConfigSet;
import org.antlr.v4.runtime.dfa.DFA;
import php.pkg.antlr4.ANTLR4Extension;
import php.runtime.Memory;
import php.runtime.annotation.Reflection;
import php.runtime.env.Environment;
import php.runtime.invoke.Invoker;
import php.runtime.lang.BaseWrapper;
import php.runtime.memory.LongMemory;
import php.runtime.memory.StringMemory;
import php.runtime.reflection.ClassEntity;

import java.lang.reflect.InvocationTargetException;
import java.util.BitSet;
import java.util.List;

@Reflection.Name("Parser")
@Reflection.Namespace(ANTLR4Extension.NS)
@Reflection.Abstract
public class PParser extends BaseWrapper<Parser> {
    public PParser(Environment env, Parser wrappedObject) {
        super(env, wrappedObject);
    }

    public PParser(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Reflection.Signature
    public static PParser get(Environment __env__, String clazz, PLexer lexer)
            throws ClassNotFoundException, NoSuchMethodException, IllegalAccessException,
                InvocationTargetException, InstantiationException {
        return new PParser(__env__, (Parser) Class.forName(clazz)
                .getConstructor(TokenStream.class)
                .newInstance(
                        new CommonTokenStream(lexer.getWrappedObject())));
    }

    @Reflection.Signature
    public void removeErrorListeners() {
        getWrappedObject().removeErrorListeners();
    }

    @Reflection.Signature
    public void addErrorListener(Invoker callback) {
        getWrappedObject().addErrorListener(new ANTLRErrorListener() {
            @Override
            public void syntaxError(Recognizer<?, ?> recognizer, Object offendingSymbol, int line, int charPositionInLine, String msg, RecognitionException e) {
                callback.callNoThrow(
                        Memory.wrap(__env__, offendingSymbol),
                        LongMemory.valueOf(line),
                        LongMemory.valueOf(charPositionInLine),
                        StringMemory.valueOf(msg));
            }

            @Override
            public void reportAmbiguity(Parser recognizer, DFA dfa, int startIndex, int stopIndex, boolean exact, BitSet ambigAlts, ATNConfigSet configs) {

            }

            @Override
            public void reportAttemptingFullContext(Parser recognizer, DFA dfa, int startIndex, int stopIndex, BitSet conflictingAlts, ATNConfigSet configs) {

            }

            @Override
            public void reportContextSensitivity(Parser recognizer, DFA dfa, int startIndex, int stopIndex, int prediction, ATNConfigSet configs) {

            }
        });
    }

    @Reflection.Signature
    public void start(List<String> methods)
            throws NoSuchMethodException, InvocationTargetException, IllegalAccessException {
        for (String method : methods) {
            getWrappedObject().getClass().getMethod(method).invoke(this.getWrappedObject());
        }
    }
}
