<?php

namespace app\index\model;

use think\Model;
use app\index\model\Orders as IndexOrders;
use app\index\model\Agents as IndexAgents;
use app\admin\model\Agentrewardagency as AdminAgency;

class Agentorderreward extends Model
{
    protected $table = 'agent_order_reward';

    /**
     * 按条件获取销售额总和
     * @param  [array] $condition [表中条件]
     * @return [float]            [销售额总和]
     */
    public function getSalesAmountSumByCondition($condition)
    {
    	return $this->where($condition)->sum('sales_amount');
    }

    /**
     * 按条件统计销售订单数
     * @param  [array] $condition [表中条件]
     * @return [int]            [销售订单数]
     */
    public function countSalesOrderByCondition($condition)
    {
    	return $this->where($condition)->count();
    }

    /**
     * 通过ID[限定时间段]获取代理商累计销售总额(直销订单(公司发货)+间销订单+直销订单(自己发货)+库存奖励)
     * @param  [string] $a_id [代理商ID]
     * @param  [array(0,1)] $time [更新时间段]
     * @param  [int] $type [类型:1全部 2直销订单 3间销订单]
     * @return [float]       [累计总额]
     */
    public function getSaleTotalByAgentId($a_id,$time = [],$type = 1)
    {
    	$total = 0;
    	$where = [
			'agent_id'    => ['in',$a_id],
			'status'      => 2,
			'reward_type' => ['in','3,4,5,8']
    	];
    	if($type != 1)
    	{
    		$where['reward_type'] = $type;
    	}
    	if(!empty($time))
    	{
            $where['update_time'] = ['between',$time];
    	}
    	// 直销订单:公司发货+间销订单
    	$total = $this->getSalesAmountSumByCondition($where);
    	return $total;
    }

    /**
     * 计算某单个代理商的销售业绩(仅计算无代理奖励订单金额)
     */
    public function getOnlyNoRewardOrderSale($a_id,$role,$time=[])
    {
        $total = 0;
        $m_agents = new IndexAgents();
        $sons_id = $m_agents->getSons($a_id,$role,3);// 直属代理ID
        if(!empty($sons_id))
        {
            $where = ['o.agent_id'=>['in',implode(',',$sons_id)],'o.isvalid'=>1,'o.order_status'=>['in','4,5'],'o.delivery_agent_id'=>1];
            if(!empty($time))
            {// 时间段不为空
                $where['o.commplete_time'] = ['between',$time];
            }
            $m_orders = new IndexOrders();
            $orders = $m_orders->alias('o')->field('o.order_number,o.order_amount_pay,pr.agent_reward')->join('product_agent_reward pr','o.pid=pr.product_id AND pr.role='.$role)->where($where)->order('o.id')->select();
            if($orders)
            {
                $order_list = [];// 订单编号
                $money_list = [];// 订单对应金额
                foreach ($orders as $key => $value)
                {
                    if($value['agent_reward'] == 0)
                    {// 对应身份等级代理奖励为0的订单
                        $order_list[] = $value['order_number'];
                        $money_list[$value['order_number']] = $value['order_amount_pay'];
                    }
                }
                $is_counted_order = $this->where(['agent_id'=>$a_id,'status'=>2,'reward_type'=>['in','2,3']])->column('order_number');// 已计算过的订单
                $surplus = array_diff($order_list, $is_counted_order);// 剩余没计算过的订单
                if($surplus)
                {
                    foreach ($surplus as $v)
                    {
                        $total += $money_list[$v];
                    }
                }
            }
        }
        return $total;
    }

    /**
     * 通过ID[限定时间段]统计代理商累计销售订单数(直销订单(公司发货)+间销订单+直销订单(自己发货))
     * @param  [string] $a_id [代理商ID]
     * @param  [array(0,1)] $time [更新时间段]
     * @param  [int] $type [类型:1全部 2直销订单 3间销订单]
     * @return [int]       [累计单数]
     */
    public function countSaleOrderTotalByAgentId($a_id,$time = [],$type = 1)
    {

    	$direct_redirect = $myself = $total = 0;
    	$where = [
			'agent_id'    => ['in',$a_id],
			'status'      => 2,
			'reward_type' => ['in','4,3']
    	];
    	$m_where = [
			'delivery_agent_id' => ['in',$a_id],
			'isvalid'           => 1,
			'order_status'      => ['in','4,5']
    	];
    	if($type != 1)
    	{
    		$where['reward_type'] = $type;
    	}
    	if(!empty($time))
    	{
			$where['update_time']      = ['between',$time];
			$m_where['commplete_time'] = ['between',$time];
    	}
    	// 直销订单:公司发货+间销订单
    	$direct_redirect = $this->countSalesOrderByCondition($where);
    	// 直销订单:自己发货
        $m_orders = new IndexOrders();
    	$myself = $m_orders->where($m_where)->count();
     	$total = $direct_redirect + $myself;
    	if($type == 4)
    	{
    		$total = $direct_redirect + $myself;
    	}else if($type == 3){
    		$total = $direct_redirect;
    	}
     	return $total;
    }

}