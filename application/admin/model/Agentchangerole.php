<?php

namespace app\admin\model;

use think\Model;

class Agentchangerole extends Model
{
	protected $table = 'agent_change_role';
	
	
	//定义公开属性 可调用
	public $reason=array(
		1=>'自动变动',
		2=>'手工变动',
	);
	
	public $type=array(
		1=>'降级',
		2=>'升级'
	);
	
	public function getTypeAttr($value)
	{
		$status = [1=>'降级',2=>'升级'];
		return $status[$value];
	}
	
	public function getReasonAttr($value)
	{
		$status = [1=>'自动变动',2=>'手工变动'];
		return $status[$value];
	}
	
	//定义和角色等级表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
}
