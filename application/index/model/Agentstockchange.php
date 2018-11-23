<?php

namespace app\index\model;

use think\Model;

class Agentstockchange extends Model
{
	protected $table = 'agent_stock_change';

	/**
	 * 获取库存明细
	 * @param  array $condition 条件
	 * @return array
	 */
	public function getStockChangeList($condition)
	{
		$return = [];
		$list = $this->alias('sc')->field('ao.agent_id AS a_id,ao.create_time,sc.order_number,sc.money,sc.change_after,sc.remark,aor.reward_type,aor.sales_amount,sc.change_type')->join('agent_orders ao','sc.order_number=ao.order_number','left')->join('agent_order_reward aor','sc.order_number = aor.order_number AND aor.agent_id=sc.agent_id','left')->where($condition)->order('ao.create_time DESC')->select();
		if($list)
		{
			$return = $list;
		}
		return $return;
	}

	//获取库存明细 有库存充值奖励的
	public function getStockChangeListByType($condition)
	{
		$res = array();
		$res = $this->field('id,agent_id,create_time,money,change_after,remark,order_number,change_type')->where($condition)->order('create_time DESC')->select();
		return $res;
	}

	// 获取充值库存总额
	public function getChargeMoneyTotalByCondition($condition)
	{
		return $this->where($condition)->sum('money');
	}

	// 获取充值库存次数
	public function getChargeTimeByCondition($condition)
	{
		return $this->where($condition)->count();
	}
	
	/**
	 * 获取库存明细
	 * @param  array $condition 条件
	 * @return array
	 */
	public function getStockChangeListAll($condition)
	{
		$return = [];
		$return=$this->where($condition)->order('id desc')->select();
		return $return;
	}
}