<?php

namespace app\index\model;

use think\Model;

class Agentstockchargeorder extends Model
{
	protected $table = 'agent_stock_charge_order';

	//生成唯一的订单ID
	public	function getOrderNumber($userId)
	{
		return $userId.date('YmdHis');
	}

	// 检查充值订单金额是否变动
	public function checkOrderPriceIsChange($order_number,$order_amount_pay)
	{
		$result = 0;
		$info = $this->field('order_amount_pay')->where(['order_number'=>$order_number])->find();
		if($info['order_amount_pay'] != $order_amount_pay)
		{
			$result = 1;
		}
		return $result;
	}
}