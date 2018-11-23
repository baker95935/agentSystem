<?php 

namespace app\admin\validate;

use think\Validate;


class Agentstockchange extends Validate
{
	protected $rule = [
		'money'  => 'require|float',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'money.require' => '充值金额必须填写',
		'money.float'     => '充值金额必须是数字',
	];
	
}

?>