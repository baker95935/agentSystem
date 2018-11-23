<?php

namespace app\admin\model;

use think\Model;

class Agentorderreturngoods extends Model
{
	protected $table = 'agent_order_return_goods';
	
	//审核状态
	public $authStatus=array(
			1=>'未审核',
			2=>'审核通过',
			3=>'审核拒绝',
	);
	
	public function getAuthStatusAttr($value)
	{
		$status = [1=>'未审核',2=>'审核通过',3=>'审核拒绝'];
		return $status[$value];
	}
	
	public function getTypeAttr($value)
	{
		$status = [1=>'商品订单',2=>'礼包订单'];
		return $status[$value];
	}
	
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
}
