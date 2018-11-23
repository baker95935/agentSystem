<?php

namespace app\admin\model;

use think\Model;

class Agentlowerstocklog extends Model
{
	protected $table = 'agent_lower_stock_log';
	
	//定义公开属性 可调用
	public $status=array(
			1=>'未降级',
			2=>'已降级',
			3=>'降级失败',
	);
	
	public function getStatusAttr($value)
	{
		$status = [1=>'未降级',2=>'已降级',3=>'降级失败'];
		return $status[$value];
	}
	
	
	//定义和角色等级表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
 
}
