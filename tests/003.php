<?php
include "../VelocityTemplateCompiler.php";
// 测试块转义
$content = <<<'EOT'
#[[
#foreach ($woogie in $boogie)
  nothing will happen to $woogie
#end
#[[]]#   ]]#
EOT;

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

