<?php 

namespace app\admin\validate;

use think\Validate;


class Agentrewardperformance extends Validate
{
	protected $rule = [
		'ratio'  => 'require|float|between:1,100',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'ratio.require' => '奖励比例必须填写',
		'ratio.float'     => '奖励比例必须是数字',
		'ratio.between'   => '奖励比例必须在1和100之间',
	];
	
}

?>