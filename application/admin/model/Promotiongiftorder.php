<?php

namespace app\admin\model;

use think\Model;

class Promotiongiftorder extends Model
{
	protected $table = 'promotion_gift_order';
	
	
	//定义公开属性 可调用
	public $status=array(
			1=>'待支付',
			2=>'待发货',
			3=>'已发货',
			4=>'交易完成',
			5=>'已关闭',
			6=>'已删除',
			7=>'售后服务',
	);
	
	public function getStatusAttr($value)
	{
		$status = [1=>'待支付',2=>'待发货',3=>'已发货',4=>'交易完成',5=>'已关闭',6=>'已删除',7=>'售后服务'];
		return $status[$value];
	}
	
	public function getPaystyleAttr($value)
	{
		$status = [0=>'未选择',1=>'线下支付',2=>'微信支付',3=>'库存支付'];
		return $status[$value];
	}
	
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
