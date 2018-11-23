<?php

namespace app\admin\model;

use think\Model;

class Agentorders extends Model
{
    protected $table = 'agent_orders';
	
    //定义公开属性 可调用
	public $orderStatus=array(
		1=>'待支付',
		2=>'待发货',
		3=>'已发货',
		4=>'交易完成',
		5=>'已关闭',
		6=>'已删除',
		7=>'售后服务',
	);
	
	public function getOrderStatusAttr($value)
	{
		$status = [1=>'待支付',2=>'待发货',3=>'已发货',4=>'交易完成',5=>'已关闭',6=>'已删除',7=>'售后服务'];
		return $status[$value];
	}
	
	public function getPaystyleAttr($value)
	{
		$status = [0=>'未选择',1=>'线下支付',2=>'微信支付',3=>'库存提货'];
		return $status[$value];
	}
	
	//生成唯一的订单ID
	public	function getOrderNumber($userId)
	{
		return $userId.date('YmdHis');
	}
 

	//定义和订单地址表的关联
	public function address()
	{
		return $this->hasOne('Agentorderconsigneeaddress','id','consignee_address_id');
	}
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
	
	//定义和发货表的关联
	public function delivery()
	{
		return $this->hasOne('Agentorderdelivery','id','delivery_id');
	}
	
	//定义和商品表的关联
	public function product()
	{
		return $this->hasOne('Productmanagement','id','pid');
	}
	
 
	 

	/**
	 * 获取订单详情
	 */
	public function agentOrderInfo($data){
	 $returnValue=$this->Where($data)
					   ->order('id', 'desc')
		    		   ->group('order_number','desc')
					   ->paginate(config('paginate.list_rows'));
	 return $returnValue;
	}
	
	//获取一个代理商的订单数
	public function getAgentsOrderNum($a_id)
	{
		return $this->where('agent_id='.$a_id.' and isvalid=1')->count();
	}

}
