package org.develnext.lexer.jphp;

import org.develnext.jphp.core.syntax.ExpressionInfo;
import org.develnext.jphp.core.syntax.SyntaxAnalyzer;
import org.develnext.jphp.core.tokenizer.Tokenizer;
import org.develnext.jphp.core.tokenizer.token.expr.operator.DynamicAccessExprToken;
import org.develnext.jphp.core.tokenizer.token.expr.value.*;
import org.develnext.jphp.core.tokenizer.token.stmt.*;
import org.develnext.lexer.jphp.classes.*;
import org.develnext.lexer.jphp.classes.token.*;
import php.runtime.env.CompileScope;
import php.runtime.env.Context;
import php.runtime.ext.support.Extension;

public class DevelNextLexerExtension extends Extension {
    public static final String NS = "develnext\\lexer";

    @Override
    public Status getStatus() {
        return Status.EXPERIMENTAL;
    }

    @Override
    public void onRegister(CompileScope scope) {
        registerJPHP(scope);
    }

    private void registerJPHP(CompileScope scope) {
        registerWrapperClass(scope, Context.class, PContext.class);

        registerWrapperClass(scope, org.develnext.jphp.core.tokenizer.token.Token.class, PSimpleToken.class);

        registerWrapperClass(scope, ExpressionInfo.class, PExpressionInfo.class);
        registerWrapperClass(scope, VariableExprToken.class, PVariableExprToken.class);
        registerWrapperClass(scope, CallExprToken.class, PCallExprToken.class);
        registerWrapperClass(scope, DynamicAccessExprToken.class, PDynamicAccessExprToken.class);
        registerWrapperClass(scope, StaticAccessExprToken.class, PStaticAccessExprToken.class);

        registerWrapperClass(scope, ExprStmtToken.class, PExprStmtToken.class);
        registerWrapperClass(scope, ClassVarStmtToken.class, PClassVarStmtToken.class);
        registerWrapperClass(scope, NameToken.class, PNameToken.class);
        registerWrapperClass(scope, FulledNameToken.class, PFulledNameToken.class);
        registerWrapperClass(scope, ConstStmtToken.class, PConstStmtToken.class);

        registerWrapperClass(scope, ArgumentStmtToken.class, PArgumentStmtToken.class);
        registerWrapperClass(scope, FunctionStmtToken.class, PFunctionStmtToken.class);
        registerWrapperClass(scope, MethodStmtToken.class, PMethodStmtToken.class);

        registerWrapperClass(scope, ClassStmtToken.class, PClassStmtToken.class);

        registerWrapperClass(scope, Tokenizer.class, PTokenizer.class);
        registerWrapperClass(scope, SyntaxAnalyzer.class, PSyntaxAnalyzer.class);
    }
}
