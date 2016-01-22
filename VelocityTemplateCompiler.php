<?php
include_once __DIR__ . '/VelocityTemplateLexer.php';
include_once __DIR__ . '/VelocityTemplateParser.php';
/**
 * Velocity模板编译器
 */
class VelocityTemplateCompiler {

    private $smarty;

    public function __construct($smarty) {
        $this->smarty = $smarty;
    }

    public function compile($content) {
        $lexer = new VelocityTemplateLexer($content);
        $parser = new VelocityTemplateParser($lexer, $this->smarty);
        return $parser->parse();
    }
}
