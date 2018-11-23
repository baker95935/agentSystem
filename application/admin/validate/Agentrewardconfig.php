<?php 

namespace app\admin\validate;

use think\Validate;


class Agentrewardconfig extends Validate
{
	protected $rule = [
		'performance_reward_clear_date'  => 'require|number|between:1,28',
	];
	
	protected $message  =   [
		'performance_reward_clear_date.require' => '结算日期必须填写',
		'performance_reward_clear_date.number'     => '结算日期必须是数字',
		'performance_reward_clear_date.between'   => '结算日期必须在1和28之间',
	];
	
}

?>