package php.pkg.antlr4;

import org.antlr.v4.runtime.Lexer;
import org.antlr.v4.runtime.Parser;
import org.antlr.v4.runtime.Token;
import php.pkg.antlr4.classes.PLexer;
import php.pkg.antlr4.classes.PParser;
import php.pkg.antlr4.classes.PToken;
import php.runtime.env.CompileScope;
import php.runtime.ext.support.Extension;

public class ANTLR4Extension extends Extension {
    public static final String NS = "php\\antlr4";
    
    @Override
    public Status getStatus() {
        return Status.EXPERIMENTAL;
    }
    
    @Override
    public void onRegister(CompileScope scope) {
        registerWrapperClass(scope, Lexer.class,  PLexer.class);
        registerWrapperClass(scope, Token.class,  PToken.class);
        registerWrapperClass(scope, Parser.class, PParser.class);
    }
}