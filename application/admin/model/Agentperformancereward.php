<?php

namespace app\admin\model;

use think\Model;

class Agentperformancereward extends Model
{
	protected $table = 'agent_performance_reward';

	//定义公开属性 可调用
	public $rewardStatus=array(
			1=>'未结算',
			2=>'已结算',
			3=>'已失效',
	);
	
	public function getStatusAttr($value)
	{
		$status = [1=>'未结算',2=>'已结算',3=>'已失效'];
		return $status[$value];
	}

	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}

	/**
	 * 按条件获取代理商的业绩分红
	 *
	 * @param $where array 自定条件
	 * @return float 合计
	 */
	public function getPerformanceByDefined($where)
	{
		$result = $this->where($where)->sum('performance_profit');
		return $result ? $result : 0.00;
	}

	/**
	 * 获取某年代理商的业绩分红明细(待结算/已结算)并按月统计
	 */
	public function getPerformanceLogOnMonth($where)
	{
		$list = [];
		$data = $this->where($where)->select();
		if($data)
		{
			foreach ($data as $key => $val)
			{
				$list[$val->year.'-'.$val->month] = [$val->status,$val->performance_profit,$val->id];
			}
		}
		ksort($list);
		return $list;
	}
}
