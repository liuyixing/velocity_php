<?php
include "../VelocityTemplateCompiler.php";
// 测试注释
$content = <<<'EOT'
## This is a single line comment.

This is text that is outside the multi-line comment.
Online visitors can see it.
#*
 Thus begins a multi-line comment. Online visitors won't
 see this text because the Velocity Templating Engine will
 ignore it.
*#
Here is text outside the multi-line comment; it is visible.

This text is visible. ## This text is not.
This text is visible.
This text is visible. #* This text, as part of a multi-line
comment, is not visible. This text is not visible; it is also
part of the multi-line comment. This text still not
visible. *# This text is outside the comment, so it is visible.
## This text is not visible.

#**
This is a VTL comment block and
may be used to store such information
as the document author and versioning
information:
@author
@version 5
*#
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
