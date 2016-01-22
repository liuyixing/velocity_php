<?php
/**
 * Velocity模板词法分析器
 */
class VelocityTemplateLexer {

    const STATE_TEXT = 1;      /** TEXT     */
    const STATE_VELOCITY = 2;  /** VELOCITY */
    const STATE_LITERAL = 3;   /** LITERAL  */
    const STATE_DQS = 4;       /** DQS      */
    const PATTERN_1 = "/\G(##.*|#\\*[\\S\\s]*?\\*#)|\G(#\\[\\[)|\G(#if\\s*)|\G(#elseif\\s*)|\G(#else)|\G(#foreach\\s*)|\G(#set\\s*)|\G(#end)|\G(\\$\\{?[A-Za-z_]\\w*)|\G(#break)|\G([\\S\\s])/iS"; 
    const PATTERN_2 = "/\G(\\$\\{?[A-Za-z_]\\w*)|\G(\\.[A-Za-z_]\\w*)|\G(\\(\\s*)|\G(\\s*\\))|\G(\\[\\s*)|\G(\\s*\\])|\G(\\s*\\+\\s*)|\G(\\s*\\-\\s*)|\G(\\s*\\*\\s*)|\G(\\s*\\/\\s*)|\G(\\s*%\\s*)|\G(\\s*&&\\s*)|\G(\\s*\\|\\|\\s*)|\G(\\s*!)|\G(\\s*==\\s*)|\G(\\s*!=\\s*)|\G(\\s*<\\s*)|\G(\\s*<=\\s*)|\G(\\s*>\\s*)|\G(\\s*>=\\s*)|\G(\\s*,\\s*)|\G(true|false)|\G(-?\\d+(?:\\.\\d+)?)|\G('[^'\\\\]*(?:\\.[^'\\\\]*)*')|\G(\\s*=\\s*)|\G(\\s*in\\s*)|\G(\\{)|\G(\\})|\G(\")|\G([\\S\\s])/iS";
    const PATTERN_3 = "/\G(\\]\\]#)|\G([\\S\\s]+?(?=\\]\\]#))/iS";
    const PATTERN_4 = "/\G(\")|\G(\\$\\{?[A-Za-z_]\\w*)|\G(\\\\.)|\G([\\S\\s])/iS";
    private $state;
    private $content;
    private $length;
    private $offset;
    private $line;
    private $column;
    private $token;
    private $stateStack = array();
    private $matchStack = array();
    const TOKEN_CONTINUE = 1;
    const TOKEN_ACCEPT = 2;

    public function __construct($content) {
        $this->content = $content;
        $this->length = strlen($content);
        $this->line = $this->column = $this->offset = 0;
        $this->state = self::STATE_TEXT;
    }

    public function scan() {
        do {
            // 到达文本末尾
            if ($this->offset >= $this->length) {
                return $this->genToken(VelocityTemplateParser::TOK_EOF);
            }
            //var_dump($this->state);
            if (!preg_match(constant('self::PATTERN_'.$this->state), $this->content, $matches, 0, $this->offset)) {
                throw new VelocityLexErrorException("Error: Unexpected input at line " . ($this->line+1) .
                    ": " . substr($this->content, $this->offset, 5) . "...");
            }
            if (!count($matches)) {
                throw new VelocityLexErrorException('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->content,
                        $this->offset, 5) . '... state TEXT');
            }
            // 过滤空匹配
            $matches = array_filter($matches, "strlen");
            //var_dump($matches);
            // 跳过第一个全局匹配
            next($matches);
            $this->tokkey = key($matches);
            $this->tokval = current($matches);
            // 调用分词处理函数
            $retval = $this->{'r'.$this->state.'_'.$this->tokkey}();
            if ($retval === self::TOKEN_CONTINUE) {
                continue;
            }
            // 重新定位
            $this->reposition();
            //var_dump($this->token);
            return $this->token;
        } while (true);
    }

    public function rescan() {
        $this->column = $this->lastColumn;
        $this->line = $this->lastLine;
        $this->offset = $this->lastOffset;
        $this->popState();
        return $this->scan();
    }

    private function r1_1() {
        $this->reposition();
        return self::TOKEN_CONTINUE;
    }

    private function r1_2() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LITERALSTART);
        $this->pushState(self::STATE_LITERAL);
    }

    private function r1_3() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_IF);
        $this->pushState(self::STATE_VELOCITY);
        array_push($this->matchStack, VelocityTemplateParser::TOK_IF);
    }

    private function r1_4() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_ELSEIF);
        $this->pushState(self::STATE_VELOCITY);
    }

    private function r1_5() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_ELSE);
    }

    private function r1_6() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_FOREACH);
        $this->pushState(self::STATE_VELOCITY);
        array_push($this->matchStack, VelocityTemplateParser::TOK_FOREACH);
    }

    private function r1_7() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_SET);
        $this->pushState(self::STATE_VELOCITY);
    }

    private function r1_8() {
        $match = array_pop($this->matchStack);
        if ($match == VelocityTemplateParser::TOK_IF) {
            $tokkey = VelocityTemplateParser::TOK_ENDIF;
        } elseif ($match == VelocityTemplateParser::TOK_FOREACH) {
            $tokkey = VelocityTemplateParser::TOK_ENDFOREACH;
        }
        $this->token = $this->genToken($tokkey);
    }

    private function r1_9() {
        if ($this->tokval[1] == '{') {
            $this->token = $this->genToken(VelocityTemplateParser::TOK_DOLLARLCBID);
        } else {
            $this->token = $this->genToken(VelocityTemplateParser::TOK_DOLLARID);
        }
        $this->pushState(self::STATE_VELOCITY);
    }

    private function r1_10() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_BREAK);
    }

    private function r1_11() {
        if (preg_match("/\G([^#\\$]+)/iS", $this->content, $matches, 0, $this->offset+1)) {
            if (count($matches)) {
                $this->tokval .= $matches[0];
            }
        }
        $this->token = $this->genToken(VelocityTemplateParser::TOK_TEXT);
    }

    private function r2_1() {
        if ($this->tokval[1] == '{') {
            $this->token = $this->genToken(VelocityTemplateParser::TOK_DOLLARLCBID);
        } else {
            $this->token = $this->genToken(VelocityTemplateParser::TOK_DOLLARID);
        }
    }

    private function r2_2() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_DOTID);
    }

    private function r2_3() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LRB);
    }

    private function r2_4() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_RRB);
    }

    private function r2_5() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LSB);
    }

    private function r2_6() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_RSB);
    }

    private function r2_7() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_ADD);
    }

    private function r2_8() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_SUB);
    }

    private function r2_9() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_MUL);
    }

    private function r2_10() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_DIV);
    }

    private function r2_11() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_MOD);
    }

    private function r2_12() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_ADD);
    }

    private function r2_13() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_OR);
    }

    private function r2_14() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_NOT);
    }

    private function r2_15() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_EQUALS);
    }

    private function r2_16() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_NOTEQUALS);
    }

    private function r2_17() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LESSTHEN);
    }

    private function r2_18() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LESSEQUAL);
    }

    private function r2_19() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_GREATERTHEN);
    }

    private function r2_20() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_GREATEREQUAL);
    }

    private function r2_21() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_COMMA);
    }

    private function r2_22() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_BOOL);
    }

    private function r2_23() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_NUM);
    }  

    private function r2_24() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_SQS);
    }  

    private function r2_25() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_EQUAL);
    }

    private function r2_26() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_IN);
    }

    private function r2_27() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LCB);
    }

    private function r2_28() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_RCB);
    }

    private function r2_29() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LDQ);
        $this->pushState(self::STATE_DQS);
    }

    private function r2_30() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_UNK);
    }

    private function r3_1() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LITERALEND);
        $this->popState();
    }

    private function r3_2() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_LITERAL);
    }

    private function r4_1() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_RDQ);
        $this->popState();
    }

    private function r4_2() {
        if ($this->tokval[1] == '{') {
            $this->token = $this->genToken(VelocityTemplateParser::TOK_DOLLARLCBID);
        } else {
            $this->token = $this->genToken(VelocityTemplateParser::TOK_DOLLARID);
        }
        $this->pushState(self::STATE_VELOCITY);
    }

    private function r4_3() {
        $this->token = $this->genToken(VelocityTemplateParser::TOK_DQS);
    }

    private function r4_4() {
        if (preg_match("/\G([^\"\\$\\\\]+)/iS", $this->content, $matches, 0, $this->offset+1)) {
            if (count($matches)) {
                $this->tokval .= $matches[0];
            }
        }
        $this->token = $this->genToken(VelocityTemplateParser::TOK_DQS);
    }

    private function reposition() {
        $this->lastColumn = $this->column;
        $this->lastLine = $this->line;
        $this->lastOffset = $this->offset;
        $len = strlen($this->tokval);
        $count = substr_count($this->tokval, "\n");
        if ($count == 0) {
            $this->column += $len; 
        } else {
            $this->column = $len - strrpos($this->tokval, "\n", -1) - 2;
        }
        $this->line += $count;
        $this->offset += $len;
    }

    private function pushState($state) {
        array_push($this->stateStack, $this->state);
        $this->state = $state;
    }

    private function popState() {
        $this->state = array_pop($this->stateStack);
    }

    private function genToken($token) {
        if ($token == VelocityTemplateParser::TOK_EOF) {
            $this->tokval = '<EOF>';
        }
        return new VelocityTemplateToken($token, $this->tokval, $this->line, $this->column);
    }
}

class VelocityTemplateToken {
    public $key;
    public $val;
    public $line;
    public $column;
    public function __construct($key, $val, $line, $column) {
        $this->key = $key;
        $this->val = $val;
        $this->line = $line + 1;
        $this->column = $column + 1;
    }
}

class VelocityLexErrorException extends Exception {
}
