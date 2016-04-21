#Velocity模板编译器-PHP实现  
---
1. 词法分析设计
利用正则表达式进行分词，并去掉注释，包括单行注释（##）和多行注释（#\* *#）
```
IF -> #if\\s*
ELSEIF -> #elseif\\s*
ELSE -> #else
FOREACH -> #foreach\\s*
SET -> #set\\s*
END -> #end  
BREAK -> #break
LITERALSTART -> #\\[\\[  
LITERAL -> [\\S\\s]+?(?=\\]\\]#)  
LITERALEND -> #\\]\\]  
DOLLARID -> \\$[A-Za-z_]\w*  
DOLLARLCBID -> \\$\\{[A-Za-z_]\w*  
DOTID -> \\.[A-Za-z_]\\w*
LRB -> \\(\\s*
RRB -> \\s*\\)
LSB -> \\[\\s* 
RSB -> \\s*\\]
LCB -> \\{
RCB -> \\}
ADD -> \\s*\\+\\s* 
SUB -> \\s*\\-\\s*
MUL -> \\s*\\*\\s*  
DIV -> \\s*\\/\\s*  
MOD -> \\s*%\\s*  
AND -> \\s*&&\\s*  
OR  -> \\s*\\|\\|\\s*  
NOT -> \\s*!  
EQUALS -> \\s*==\\s*  
NOTEQUALS -> \\s*!=\\s*  
LESSTHAN -> \\s*<\\s*  
LESSEQUAL -> \\s*<=\\s*  
GREATERTHAN -> \\s*>\\s*  
GREATEREQUAL -> \\s*>=\\s*  
COMMA -> \\s*,\\s* 
BOOL -> (true|false)  
NUM -> -?\\d+(?:\\.\\d+)?  
SQS -> '[^'\\]*(?:\\.[^'\\]*)*'
EQUAL -> \\s*=\\s*
IN -> \\s*in\\s*
LDQ -> \\"  
DQS -> [^\\$"\\]+  
RDQ -> \\" 
TEXT -> \\S\\s
```
2. 语法分析设计
```
stmts -> stmt stmts  
      | ∈  
```
```
stmt -> IF (expr) stmts optelseifs optelse ENDIF  
      | FOREACH (DOLLARID IN ref) stmts ENFOREACH  
      | SET (DOLLARID = expr)  
      | LITERALSTART LITERAL LITERALEND  
      | DOLLARID optindexes optproperties  
      | DOLLARLCBID optindexes optproperties RCB  
      | TEXT  
```
```
optelseifs -> ELSEIF(expr) stmts optelseifs  
      | ∈  
```
```
optelse -> ELSE stmts  
      | ∈  
```
```
ref -> DOLLARID optindexes optproperties  
```
```
optindexes -> LSB expr RSB optindexes   
      | ∈  
```
```
optproperties -> DOTID optindexesparams optproperties  
      | ∈  
```
```
optindexesparams -> LSB expr RSB optindexes  
      | LRB optparams RRB optindexes  
      | ∈  
```
```
expr -> BOOL optexprrest  
      | NUM optexprrest  
      | DOLLARID optindexes optproperties optexprrest  
      | DOLLARLCBID optindexes optproperties RCB optexprrest  
      | SQS optexprrest  
      | LDQS optdqs RDQS optexprrest  
      | NOT expr  
      | LRB expr RRB 
```
```
optdqs -> DOLLARID optindexes optproperties optdqs  
      | DOLLARLCBID optindexes optproperties RCB optdqs  
      | DQS optdqs  
      | ∈  
```
```
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
```
```
optparams -> expr optparam  
      | ∈  
```
```
optparam -> COMMA expr optparam  
      | ∈  
```