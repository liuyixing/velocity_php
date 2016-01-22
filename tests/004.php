<?php
include "../VelocityTemplateCompiler.php";
// 测试引用
$content = <<<'EOT'
$mudSlinger
$customer.Address
$customer.getAddress()
$page.setTitle( "My Home Page", 1 )
$page.setTitle( "My Home$mudSlinger Page" )
$page.setTitle( "My Home$mudSlinger.Address Page" )
$page.setTitle( "My Home$mudSlinger $a.b[0] Page" )
$page.setTitle( "My Home$mudSlinger.getAddress() Page" )
$page.setTitle( "My Home${mudSlinger} Page" )
$page.setTitle( $title )
$page.setTitle( $title, $title2 )
$page.setTitle( $title, 1 )
$page.setTitle( $page.title )
$page.setTitle( $page.getTitle() )
$page.setTitle( $pge.getTitle(), $page.getTitle(1, $title, $content, $c[0].ok.get('name')))
##$person.setAttributes( ["Strange", "Weird", "Excited"] )
$foo.bar[1].junk
$foo.callMethod()[1]
$foo["apple"][4]
$foo["bar"]
$foo[$i]
$foo[0]
${foo.bar[1].junk}
${page.setTitle( $pge.getTitle(), $page.getTitle(1, $title, $content, $c[0].ok.get('name')))}
${foo[0]}
${foo}
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

