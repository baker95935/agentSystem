<?php 

namespace app\admin\validate;

use think\Validate;


class Agentlevel extends Validate
{
	protected $rule = [
		'name'  => 'require|chs|max:20',
		'deep'  => 'require|number|between:0,6',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'name.require' => '代理商等级必须填写',
		'name.chs'     => '等级名称必须为中文',
		'name.max'   => '等级名称最多20个字符',
		'deep.require' => '推荐奖励深度必须填写',
		'deep.number'     => '奖励深度必须是数字',
		'deep.between'     => '奖励深度必须0到6之间',
	];
}

?>