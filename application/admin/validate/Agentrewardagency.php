<?php 

namespace app\admin\validate;

use think\Validate;


class Agentrewardagency extends Validate
{
	protected $rule = [
		'ratio'  => 'require|float|between:1,100',
		'pre_deposit'  => 'require|float',
		'first_goods_number'  => 'require|float',
		'lowest_limit'  => 'require|float',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'ratio.require' => '奖励比例必须填写',
		'ratio.float'     => '奖励比例必须是数字',
		'ratio.between'   => '奖励比例必须在1和100之间',
		
		'first_goods_number.require' => '首次拿货数量必须填写',
		'first_goods_number.float'     => '首次拿货数量必须是数字',
		'first_goods_number.between'   => '首次拿货数量必须在1和100之间',
		
		'pre_deposit.require' => '预存款额度必须填写',
		'pre_deposit.float'     => '预存款额度必须是数字',
		
	 
		
		'lowest_limit.require' => '最低库存额度必须填写',
		'lowest_limit.float'     => '最低库存额度必须是数字',
	];
	
}

?>