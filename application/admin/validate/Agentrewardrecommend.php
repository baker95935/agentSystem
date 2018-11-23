<?php 

namespace app\admin\validate;

use think\Validate;


class Agentrewardrecommend extends Validate
{
	protected $rule = [
		'value'  => 'float|between:0,100',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'value.float'     => '比例必须是数字',
		'value.between'   => '数字必须在0和100之间',
 
	];
	
}

?>