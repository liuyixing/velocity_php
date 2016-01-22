<?php
include "../VelocityTemplateCompiler.php";
// 测试转义
$content = <<<'EOT'
\#include( "a.txt" ) renders as #include( "a.txt" )

\\#include ( "a.txt" ) renders as \<contents of a.txt>

$email 
\$email
\\$email
\\\$email
EOT;
//$email无定义，原样输出，否则按照转义规则输出

// smarty
class Smarty {
    public $left_delimiter;
    public $right_delimiter; 
}
$smarty = new Smarty;
$smarty->left_delimiter = "{";
$smarty->right_delimiter = "}";
$compiler = new VelocityTemplateCompiler($smarty);
$content = $compiler->compile($content);
echo "result: \n";
echo $content."\n";
var_dump($content);
