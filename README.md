# velocity_php
PHP实现的Velocity模板编译器

Velocity编译器设计

1.词法分析
去掉注释 ## #* *#

2.语法分析  
stmts -> stmt stmts  
         | ∈  

stmt -> IF (expr) stmts optelseifs optelse ENDIF
      | FOREACH (DOLLARID IN ref) stmts ENFOREACH
      | SET (DOLLARID = expr)
      | LITERALSTART LITERAL LITERALEND
      | DOLLARID optindexes optproperties
      | DOLLARLCBID optindexes optproperties RCB
      | TEXT

optelseifs -> ELSEIF(expr) stmts optelseifs
         | ∈

optelse -> ELSE stmts
         | ∈

ref -> DOLLARID optindexes optproperties

optindexes -> LSB expr RSB optindexes 
           | ∈

optproperties -> DOTID optindexesparams optproperties
               | ∈

optindexesparams -> LSB expr RSB optindexes
         | LRB optparams RRB optindexes
         | ∈

expr -> BOOL optexprrest
      | NUM optexprrest
      | DOLLARID optindexes optproperties optexprrest
      | DOLLARLCBID optindexes optproperties RCB optexprrest
      | SQS optexprrest
      | LDQS optdqs RDQS optexprrest
      | NOT expr
      | LRB expr RRB

optdqs -> DOLLARID optindexes optproperties optdqs
      | DOLLARLCBID optindexes optproperties RCB optdqs
      | DQS optdqs
      | ∈

optexprrest -> ADD expr
      | SUB expr
      | MUL expr
      | DIV expr
      | MOD expr
      | AND expr
      | OR expr
      | EQUALS expr
      | NOTEQUALS expr
      | LESSTHAN expr
      | LESSEQUAL expr
      | GREATERTHAN expr
      | GREATEREQUAL expr
      | ∈

optparams -> expr optparam
           | ∈

optparam -> COMMA expr optparam
           | ∈


IF -> #if
ELSEIF -> #elseif
ELSE -> #else
END -> #end
FOREACH -> #foreach
SET -> #set
IN -> in
LITERALSTART -> #\\[\\[
LITERAL -> [\\S\\s]+?(?=\\]\\]#)
LITERALEND -> #\\]\\]
DOLLARID -> \\$[A-Za-z_]\w*
DOLLARLCBID -> \\$\\{[A-Za-z_]\w*
DOTID -> \\.[A-Za-z_]\\w*
LRB -> \\(
RRB -> \\)
LSB -> \\[
RSB -> \\]
LCB -> \\{
RCB -> \\}
BOOL -> (true|false)
NUM -> -?\\d+(?:\\.\\d+)?
SQS -> '[^'\\]*(?:\\.[^'\\]*)*'
LDQ -> "
DQS -> [^\\$"\\]+
RDQ -> "
ADD -> \\+
SUB -> \\-
MUL -> \\*
DIV -> /
MOD -> %
AND -> &&
OR  -> \\|\\|
NOT -> !
EQUALS -> ==
NOTEQUALS -> !=
LESSTHAN -> <
LESSEQUAL -> <=
GREATERTHAN -> >
GREATEREQUAL -> >=
COMMA -> ,
