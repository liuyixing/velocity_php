<?php
include "../VelocityTemplateCompiler.php";
// 测试set
$content = <<<'EOT'
#set ($bar = "ribonucleic acid")
#set ($bar = "ribonucleic")
#set ($bar = $foo)
#set ($bar = $foo.goo[1])
#set ($bar = $foo.goo($person.name, $person.getAge()))
#set ($bar = $foo.goo($person.name, $person.getAge()))
#set ($bar = $foo.goo($person.name, $person.getAge()))
#set ($bar = $foo.goo($person.name, $person.getAge()))
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

