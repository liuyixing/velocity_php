<?php
include "../VelocityTemplateCompiler.php";
// 测试foreach和if
$content = <<<'EOT'
#foreach( $mud in $mudsOnSpecial )
   #if ( $customer.hasPurchased($mud) )
      <tr>
        <td>
          $flogger.getPromo( $mud )
        </td>
      </tr>
      #break
   #end
#end
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

