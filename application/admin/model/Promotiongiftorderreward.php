<?php

namespace app\admin\model;

use think\Model;

class Promotiongiftorderreward extends Model
{
	protected $table = 'promotion_gift_order_reward';
	
	public function getStatusAttr($value)
	{
		$status = [1=>'未结算',2=>'已结算',3=>'失效'];
		return $status[$value];
	}
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
}
