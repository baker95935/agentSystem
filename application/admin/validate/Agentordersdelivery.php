<?php 

namespace app\admin\validate;

use think\Validate;


class Agentordersdelivery extends Validate
{
	protected $rule = [
		'express_name'  => 'require',
		'express_number'  => 'require',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'express_number.require' => '快递单号必须填写',
		'express_name.require' => '发货方式必须填写',
	];
	
}

?>