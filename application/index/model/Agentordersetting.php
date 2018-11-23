<?php

namespace app\index\model;

use think\Model;

class Agentordersetting extends Model
{
	protected $table = 'agent_order_setting';
	
	
	//获取一个订单号的发货方式
	public function getDeliveryWayByOrderNumber($order_number)
	{
		$res=1;//1是公司发货 2是代理商发货3是2个都发
		
		$order=model('Orders');
		$create_time=$order->where("order_number='".$order_number."'")->value('create_time');
		
		$settingInfo=$this->find();
		
		//2个都选，那么根据订单时长进行判断
		if($settingInfo['consignment_info']==3) {
			
			$time_span=$settingInfo['time_span']*24*60*60;
			$now=time();
			if($time_span+$create_time>$now) {
				$res=3;
			} else {
				$res=1;
			}
			
		}
		$settingInfo['consignment_info']==2 && $res=2;
		
		return $res;
		
	}
}
