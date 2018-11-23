<?php

namespace app\admin\model;

use think\Model;

class Agentprofit extends Model
{
	protected $table = 'agent_profit';
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
	
	//定义和订单表的关联
	public function orders()
	{
		return $this->hasOne('Agentorders','order_number','order_number');
	}
	
	//定义和订单表的关联
	public function giftOrders()
	{
		return $this->hasOne('Promotiongiftorder','order_number','order_number');
	}
}
