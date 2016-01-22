<?php
/**
 * Velocity模板语法分析器
 * @TODO: 
 * 1.添加保护机制，防止死循环和无限递归
 * 2.[1, 2, 3] 和 [2..3]语法支持
 * 3.{'a': 1, 'b': 2} 语法支持
 */
class VelocityTemplateParser {
    
    /* Token常量 */
    const TOK_IF = 1;
    const TOK_ELSEIF = 2;
    const TOK_ELSE = 3;
    const TOK_ENDIF = 4;
    const TOK_FOREACH = 5;
    const TOK_ENDFOREACH = 6;
    const TOK_SET = 7;
    const TOK_IN = 8;
    const TOK_LITERALSTART = 9;
    const TOK_LITERAL = 10;
    const TOK_LITERALEND = 11;
    const TOK_DOLLARID = 12;
    const TOK_DOTID = 13;
    const TOK_LRB = 14;
    const TOK_RRB = 15;
    const TOK_LSB = 16;
    const TOK_RSB = 17;
    const TOK_BOOL = 18;
    const TOK_NUM = 19;
    const TOK_SQS = 20;
    const TOK_ADD = 21;
    const TOK_SUB = 22;
    const TOK_MUL = 23;
    const TOK_DIV = 24;
    const TOK_MOD = 25;
    const TOK_AND = 26;
    const TOK_OR = 27;
    const TOK_NOT = 28;
    const TOK_EQUALS = 29;
    const TOK_NOTEQUALS = 30;
    const TOK_LESSTHEN = 31;
    const TOK_LESSEQUAL = 32;
    const TOK_GREATERTHEN = 33;
    const TOK_GREATEREQUAL = 34;
    const TOK_COMMA = 35;
    const TOK_TEXT = 36;
    const TOK_EQUAL = 37;
    const TOK_DOLLARLCBID = 38;
    const TOK_LCB = 39;
    const TOK_RCB = 40;
    const TOK_BREAK = 41;
    const TOK_LDQ = 42;
    const TOK_DQS = 43;
    const TOK_RDQ = 44;
    const TOK_UNK = 98;
    const TOK_EOF = 99;
    
    /* Token名字 */
    private static $tokenNames = array(
        '<VTL>', '#if', '#elseif', '#else', '#end',
        '#foreach', '#end', '#set', 'in', '#[[',
        '<LITERAL>', ']]#', '$<ID>', '.<ID>', '(', ')',
        '[', ']', '<BOOL>', '<NUM>', '<SQS>', '+', '-',
        '*', '/', '%', '&&', '||', '!', '==', '!=',
        '<', '<=', '>', '>=', ',', '<TEXT>', '=',
        '${<ID>', '{', '}', '#break', '<LDQ>',
        '<DQS>', '<RDQ>', '<UNKNOWN>', '<EOF>',
    );

    /* 语句块结束标志 */
    private static $fileUntil = array(self::TOK_EOF);
    private static $ifUntil = array(self::TOK_ELSEIF, self::TOK_ELSE, self::TOK_ENDIF);
    private static $elseUntil = array(self::TOK_ENDIF);
    private static $foreachUntil = array(self::TOK_BREAK, self::TOK_ENDFOREACH);

    private $lexer;
    private $lookahead;
    private $token;
    private $smarty;
    private $smarty_ld;
    private $smarty_rd;

    public function __construct($lexer, $smarty) {
        $this->lexer = $lexer;
        // 初始化向前看符号
        $this->token = $lexer->scan();
        $this->lookahead = $this->token->key;
        $this->smarty = $smarty;
        $this->smarty_ld = $smarty->left_delimiter;
        $this->smarty_rd = $smarty->right_delimiter;
    }

    public function parse() {
        return $this->stmts(self::TOK_EOF);
    }

    private function stmts($block) {
        if ($block == self::TOK_IF || $block == self::TOK_ELSEIF) {
            $until = self::$ifUntil;
        } elseif ($block == self::TOK_ELSE) {
            $until = self::$elseUntil;
        } elseif ($block == self::TOK_FOREACH) {
            $until = self::$foreachUntil;
        } else {
            $until = self::$fileUntil;
        }
        $code = '';
        // 循环进行语句解析，直到语句块末尾
        while (!in_array($this->lookahead, $until)) {
            $code .= $this->stmt();
        }
        return $code;
    }

    private function stmt() {
        $code = '';
        switch ($this->lookahead) {
            case self::TOK_IF:
                $this->match(self::TOK_IF);
                $this->match(self::TOK_LRB); $expr = $this->expr(); $this->match(self::TOK_RRB); $this->endofstate();
                $stmts = $this->stmts(self::TOK_IF); $optelseifs = $this->optelseifs(); $optelse = $this->optelse();
                $this->match(self::TOK_ENDIF);
                $code .= $this->smarty_ld.'if ('.$expr.')'.$this->smarty_rd.$stmts.$optelseifs.$optelse.$this->smarty_ld.'/if'.$this->smarty_rd;
                return $code;
            case self::TOK_FOREACH:
                $this->match(self::TOK_FOREACH);
                $this->match(self::TOK_LRB); $dollarid = $this->token->val; $this->match(self::TOK_DOLLARID);
                $this->match(self::TOK_IN); $ref = $this->ref(); $this->match(self::TOK_RRB); $this->endofstate();
                $stmts = $this->stmts(self::TOK_FOREACH);
                while ($this->lookahead == self::TOK_BREAK) {
                    $this->match(self::TOK_BREAK); $stmts .= $this->smarty_ld.'break'.$this->smarty_rd.$this->stmts(self::TOK_FOREACH);
                }
                $this->match(self::TOK_ENDFOREACH);
                $code .= $this->smarty_ld.'foreach '.$ref.' as '.$dollarid.$this->smarty_rd.$stmts.$this->smarty_ld.'/foreach'.$this->smarty_rd;
                return $code;
            case self::TOK_SET:
                $this->match(self::TOK_SET);
                $this->match(self::TOK_LRB); $dollarid = $this->token->val; $this->match(self::TOK_DOLLARID);
                $this->match(self::TOK_EQUAL); $expr = $this->expr(); $this->match(self::TOK_RRB); $this->endofstate();
                $code .= $this->smarty_ld.$dollarid.'='.$expr.$this->smarty_rd;
                return $code;
            case self::TOK_LITERALSTART:
                $this->match(self::TOK_LITERALSTART); $literal = $this->token->val; $this->match(self::TOK_LITERAL); $this->match(self::TOK_LITERALEND);
                $code .= $this->smarty_ld.'literal'.$this->smarty_rd.$literal.$this->smarty_ld.'/literal'.$this->smarty_rd;
                return $code;
            case self::TOK_DOLLARID:
                $dollarid = $this->token->val; $this->match(self::TOK_DOLLARID); $optindexes = $this->optindexes();
                $optproperties = $this->optproperties(); $this->endofstate();
                $code .= $this->smarty_ld.$dollarid.$optindexes.$optproperties.$this->smarty_rd;
                return $code;
            case self::TOK_DOLLARLCBID:
                $dollarid = '$'.substr($this->token->val, 2); $this->match(self::TOK_DOLLARLCBID); $optindexes = $this->optindexes();
                $optproperties = $this->optproperties(); $this->match(self::TOK_RCB); $this->endofstate();
                $code .= $this->smarty_ld.$dollarid.$optindexes.$optproperties.$this->smarty_rd;
                return $code;
            default: // TOK_TEXT
                $text = $this->token->val; $this->match($this->lookahead);
                $code .= $text;
                return $code;
        }
    }

    private function optelseifs() {
        $code = '';
        if ($this->lookahead == self::TOK_ELSEIF) {
            $this->match(self::TOK_ELSEIF); $this->match(self::TOK_LRB); $expr = $this->expr(); $this->match(self::TOK_RRB); $this->endofstate();
            $stmts = $this->stmts(self::TOK_ELSEIF); $optelseifs = $this->optelseifs();
            $code .= $this->smarty_ld.'elseif ('.$expr.')'.$this->smarty_rd.$stmts.$optelseifs;
        }
        return $code;
    }

    private function optelse() {
        $code = '';
        if ($this->lookahead == self::TOK_ELSE) {
            $this->match(self::TOK_ELSE); $stmts = $this->stmts(self::TOK_ELSE);
            $code .= $this->smarty_ld.'else'.$this->smarty_rd.$stmts;
        }
        return $code;
    }

    private function ref() {
        $code = '';
        $dollarid = $this->token->val; $this->match(self::TOK_DOLLARID); $optindexes = $this->optindexes(); $optproperties = $this->optproperties();
        $code .= $dollarid.$optindexes.$optproperties;
        return $code;
    }

    private function optindexes() {
        $code = '';
        while ($this->lookahead == self::TOK_LSB) {
            $this->match(self::TOK_LSB); $expr = $this->expr(); $this->match(self::TOK_RSB);
            $code .= '->{'.$expr.'}';
        }
        return $code;
    }

    private function optproperties() {
        $code = '';
        while ($this->lookahead == self::TOK_DOTID) {
            $id = substr($this->token->val, 1); $this->match(self::TOK_DOTID); $optindexesparams = $this->optindexesparams();
            $code .= '->'.$id.$optindexesparams;
        }
        return $code;
    }

    private function optindexesparams() {
        $code = '';
        if ($this->lookahead == self::TOK_LSB) {
            $this->match(self::TOK_LSB); $expr = $this->expr(); $this->match(self::TOK_RSB); $optindexes = $this->optindexes();
            $code .= '->{'.$expr.'}'.$optindexes;
        } elseif ($this->lookahead == self::TOK_LRB) {
            $this->match(self::TOK_LRB); $optparams = $this->optparams(); $this->match(self::TOK_RRB); $optindexes = $this->optindexes();
            $code .= '('.$optparams.')'.$optindexes;
        }
        return $code;
    }

    private function expr() {
        $code = '';
        switch ($this->lookahead) {
            case self::TOK_BOOL:
            case self::TOK_NUM:
            case self::TOK_SQS:
                $val = $this->token->val; $this->match($this->lookahead); $optexprrest = $this->optexprrest();
                $code .= $val.$optexprrest;
                break;
            case self::TOK_LDQ:
                $this->match(self::TOK_LDQ); $optdqs = $this->optdqs(); $this->match(self::TOK_RDQ);
                $code .= '"'.$optdqs.'"';
                break;
            case self::TOK_DOLLARID:
                $dollarid = $this->token->val; $this->match(self::TOK_DOLLARID); $optindexes = $this->optindexes();
                $optproperties = $this->optproperties(); $optexprrest = $this->optexprrest();
                $code .= $dollarid.$optindexes.$optproperties.$optexprrest;
                break;
            case self::TOK_DOLLARLCBID:
                $dollarid = '$'.substr($this->token->val, 2); $this->match(self::TOK_DOLLARLCBID); $optindexes = $this->optindexes();
                $optproperties = $this->optproperties(); $this->match(self::TOK_RCB); $optexprrest = $this->optexprrest();
                $code .= $dollarid.$optindexes.$optproperties.$optexprrest;
                break;
            case self::TOK_NOT:
                $this->match(self::TOK_NOT); $expr = $this->expr();
                $code .= '!'.$expr;
                break;
            case self::TOK_LRB:
                $this->match(self::TOK_LRB); $expr = $this->expr(); $this->match(self::TOK_RRB);
                $code .= '('.$expr.')';
                break;
            default:
                $this->error('Encountered "'.$this->token->val.'" at [line '.$this->token->line.', column '.$this->token->column.
                '] Was expecting one of: "'.self::$tokenNames[self::TOK_BOOL].'", "'.self::$tokenNames[self::TOK_NUM].'", "'.
                self::$tokenNames[self::TOK_SQS].'", "'.self::$tokenNames[self::TOK_DOLLARID].'", "'.
                self::$tokenNames[self::TOK_NOT].'", "'.self::$tokenNames[self::TOK_LRB].'"');
        }
        return $code;
    }

    private function optdqs() {
        $code = '';
        while ($this->lookahead == self::TOK_DOLLARID || $this->lookahead == self::TOK_DOLLARLCBID
            || $this->lookahead == self::TOK_DQS) {
            switch ($this->lookahead) {
                case self::TOK_DOLLARID:
                    $dollarid = $this->token->val; $this->match(self::TOK_DOLLARID); $optindexes = $this->optindexes();
                    $optproperties = $this->optproperties(); $this->endofstate();
                    $code .= $dollarid.$optindexes.$optproperties;
                    break;
                case self::TOK_DOLLARLCBID:
                    $dollarid = '$'.substr($this->token->val, 2); $this->match(self::TOK_DOLLARLCBID); $optindexes = $this->optindexes();
                    $optproperties = $this->optproperties(); $this->match(self::TOK_RCB); $this->endofstate();
                    $code .= $dollarid.$optindexes.$optproperties;
                    break;
                case self::TOK_DQS:
                    $dqs = $this->token->val; $this->match(self::TOK_DQS);
                    $code .= $dqs;
                    break;
            }
        }
        return $code;
    }

    private function optexprrest() {
        $code = '';
        switch ($this->lookahead) {
            case self::TOK_ADD:
                $this->match(self::TOK_ADD); $expr = $this->expr(); $code .= '+'.$expr; break;
            case self::TOK_SUB:
                $this->match(self::TOK_SUB); $expr = $this->expr(); $code .= '-'.$expr; break;
            case self::TOK_MUL:
                $this->match(self::TOK_MUL); $expr = $this->expr(); $code .= '*'.$expr; break;
            case self::TOK_DIV:
                $this->match(self::TOK_DIV); $expr = $this->expr(); $code .= '/'.$expr; break;
            case self::TOK_MOD:
                $this->match(self::TOK_MOD); $expr = $this->expr(); $code .= '%'.$expr; break;
            case self::TOK_AND:
                $this->match(self::TOK_AND); $expr = $this->expr(); $code .= '&&'.$expr; break;
            case self::TOK_OR:
                $this->match(self::TOK_OR); $expr = $this->expr(); $code .= '||'.$expr; break;
            case self::TOK_EQUALS:
                $this->match(self::TOK_EQUALS); $expr = $this->expr(); $code .= '=='.$expr; break;
            case self::TOK_NOTEQUALS:
                $this->match(self::TOK_NOTEQUALS); $expr = $this->expr(); $code .= '!='.$expr; break;
            case self::TOK_LESSTHEN:
                $this->match(self::TOK_LESSTHEN); $expr = $this->expr(); $code .= '<'.$expr; break;
            case self::TOK_LESSEQUAL:
                $this->match(self::TOK_LESSEQUAL); $expr = $this->expr(); $code .= '<='.$expr; break;
            case self::TOK_GREATERTHEN:
                $this->match(self::TOK_GREATERTHEN); $expr = $this->expr(); $code .= '>'.$expr; break;
            case self::TOK_GREATEREQUAL:
                $this->match(self::TOK_GREATEREQUAL); $expr = $this->expr(); $code .= '>='.$expr; break;
        }
        return $code;
    }

    private function optparams() {
        $code = '';
        if ($this->lookahead == self::TOK_BOOL || $this->lookahead == self::TOK_NUM
            || $this->lookahead == self::TOK_SQS || $this->lookahead == self::TOK_DOLLARID
            || $this->lookahead == self::TOK_NOT || $this->lookahead == self::TOK_LRB
            || $this->lookahead == self::TOK_LDQ) {
            $expr = $this->expr();
            $code .= $expr;
            while ($this->lookahead == self::TOK_COMMA) {
                $this->match(self::TOK_COMMA); $expr = $this->expr();
                $code .= ','.$expr;
            }
        }
        return $code;
    }

    private function match($expect) {
        if ($this->lookahead == $expect) {
            $this->move();
        } else {
            $this->error('Encountered "'.$this->token->val.'" at [line '.$this->token->line.', column '.$this->token->column.
                '] Was expecting of: "'.self::$tokenNames[$expect].'"');
        }
    }

    private function endofstate() {
        if ($this->lookahead != self::TOK_EOF) {
            $this->token = $this->lexer->rescan();
            $this->lookahead = $this->token->key;
        }
    }

    private function move() {
        $this->token = $this->lexer->scan();
        $this->lookahead = $this->token->key;
    }

    private function error($msg) {
        throw new VelocityParseErrorException($msg);
    }
}

class VelocityParseErrorException extends Exception {
}

