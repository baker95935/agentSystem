<?php 

namespace app\admin\validate;

use think\Validate;


class Promotiongiftreward extends Validate
{
	protected $rule = [
		'value'  => 'require|float|between:1,100',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'value.require' => '奖励比例必须填写',
		'value.float'     => '奖励比例必须是数字',
		'value.between'   => '奖励比例必须在1和100之间',
	];
	
}

?>