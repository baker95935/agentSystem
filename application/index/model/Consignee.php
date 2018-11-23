<?php

namespace app\index\model;

use think\Model;
use think\Cache;

class Consignee extends Model
{
	//定义缓存时间
	protected $cache_time=1800;
	
	protected $table = 'agent_order_consignee_address';
	
	//根据订单号获取收货地址
	public function getConsigneeInfoByOrderNumber($order_number)
	{
		$res=array();
		 
		 
		$memKey='getConsigneeInfoByOrderNumber'.$order_number;
		$res=Cache::get($memKey);
		
		if(empty($res)) {
			$res=$this->where(array('order_number'=>$order_number))->find();
			
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		
		return $res;
	}
}
