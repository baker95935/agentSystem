<?php

namespace app\admin\model;

use think\Model;

class Agentorderreward extends Model
{
	protected $table = 'agent_order_reward';
	
	public function getStatusAttr($value)
	{
		$status = [1=>'未结算',2=>'已结算'];
		return $status[$value];
	}
	
	
	public $rewardType=array(
			1=>'招商收益',
			2=>'直销收益',
			3=>'间接收益',
			4=>'直接收益',
	);
	 
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
	
	//获取订单的奖励信息，
	public function getOrdersRewardInfo($order_number,$reward_type,$product_id)
	{
		$res=array();
		
		$res['list']=$this->where("order_number='".$order_number."' and  reward_type=".$reward_type." and product_id=".$product_id)->field('product_id,agent_id,create_time,wholesale_reward,selfsale_reward,directsale_reward,recommend_reward,recommend_hierarchy,status')->select();
		$res['count']=$this->where("order_number='".$order_number."' and  reward_type=".$reward_type.' and product_id='.$product_id)->count();
		
		return $res;
	}
}
