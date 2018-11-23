<?php 

namespace app\admin\validate;

use think\Validate;


class Agentordersetting extends Validate
{
	protected $rule = [
		'auto_confirm_time'  => 'require|number|between:1,100',
		'consignment_info'  => 'require',
		'time_span'  => 'require|number|between:1,100',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'auto_confirm_time.require' => '自动确认收货时间必须填写',
		'auto_confirm_time.number' => '自动确认收货时间必须是数字',
		'auto_confirm_time.between' => '自动确认收货时间是1到100之间的数字',
		
		'consignment_info.require' => '发货方必须选择',
		'time_span.require' => '时间范围必须填写',
		'time_span.number' => '时间范围必须是数字',
		'time_span.between' => '时间范围是1到100之间的数字',
	];
	
}

?>