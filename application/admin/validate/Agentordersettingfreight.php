<?php 

namespace app\admin\validate;

use think\Validate;


class Agentordersettingfreight extends Validate
{
	protected $rule = [
		'order_amount'  => 'require|float|between:0,100000000',
		'freight'  => 'require|float|between:0,100000000',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'order_amount.require' => '订单额度必须填写',
		'order_amount.float' => '订单额度必须是数字',
		'order_amount.between' => '订单额度是0到100000000之间的数字',
		
		'freight.require' => '运费必须填写',
		'freight.float' => '运费必须是数字',
		'freight.between' => '运费是0到100000000之间的数字',
	];
	
}

?>