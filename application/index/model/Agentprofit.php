<?php

namespace app\index\model;

use think\Model;

class Agentprofit extends Model
{
	//代理商收益表
    protected $table = 'agent_profit';

    /**
     * 某代理商累计招商奖励/代理奖励/业绩分红总额
     *
     * @param $arr['a_id'=> int 代理商ID,'type'=> int 奖励类型]
     * @return float 总额
     */
    public function getTotalXRewardByAid($arr)
    {
    	$result = $this->where(['agent_id'=>$arr['a_id'],'type'=>$arr['type']])->sum('profit');
    	return $result ? $result : 0.00;
    }

    /**
     * 按条件查询代理商的某种奖励之和
     *
     * @param $condition array 自定义条件
	 * @param $field string 统计字段
     * @return float 金额
     */
    public function getRewardByDefined($condition)
    {
    	$result = $this->where($condition)->sum('profit');
    	return $result ? $result : 0.00;
    }

    /**
     * 按条件查询代理商的奖励明细(累计)
     *
     * @param $condition array 自定义条件
     * @param $field string 查询字段
     * @return array
     */
    public function getRewardLogsByDefined($condition,$field='')
    {
    	$field = empty($field) ? 'p.profit, p.create_time, p.type, o.agent_id, a.nickname, a.wechat' : $field;
    	$result = $this->alias('p')->field($field)->join('agent_orders o','p.order_number=o.order_number')->join('agents a','o.agent_id=a.agent_id','left')->where($condition)->order('p.create_time DESC')->select();
    	return $result;
    }

    /**
     * 按条件查询代理商的奖励明细(累计)-礼包奖励
     *
     * @param $condition array 自定义条件
     * @param $field string 查询字段
     * @return array
     */
    public function getPromotionGiftReward($condition,$field='')
    {
    	$field = empty($field) ? 'p.profit, p.create_time, p.type, o.agent_id' : $field;
    	$result = $this->alias('p')->field($field)->join('promotion_gift_order o','p.order_number=o.order_number')->join('promotion_gift_info pgi','o.gift_id=pgi.id','left')->where($condition)->order('p.create_time DESC')->select();
    	return $result;
    }
    
    /**
     * 按条件查询代理商的奖励明细-库存充值奖励
     *
     * @param $condition array 自定义条件
     * @param $field string 查询字段
     * @return array
     */
    public function getStockChargeReward($condition,$field='')
    {
    	$field = empty($field) ? 'p.profit, p.create_time, p.type, p.agent_id,p.relation_id' : $field;
    	$result = $this->alias('p')->field($field)->where($condition)->order('p.create_time DESC')->select();
    	return $result;
    }
    
    //获取收益明细  有库存充值奖励的
    public function getRewardLogsByType($condition)
    {
    	$res = array(); 
    	$res = $this->alias('p')->field('id,profit,create_time,type,agent_id,sales_amount,order_number,relation_id')->where($condition)->order('create_time DESC')->select();
    	return $res;
    }

}