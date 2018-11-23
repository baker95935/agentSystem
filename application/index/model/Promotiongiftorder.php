<?php

namespace app\index\model;

use think\Model;

class Promotiongiftorder extends Model
{
	protected $table = 'promotion_gift_order';
	
	
	//生成唯一的订单ID
	public	function getOrderNumber($userId)
	{
		return $userId.date('YmdHis');
	}
	
	//定义和礼包表的关联
	public function gift()
	{
		return $this->hasOne('Promotiongift','id','gift_id');
	}
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
}
