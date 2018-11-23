<?php

namespace app\admin\model;

use think\Model;
use think\Cache;
use think\Db;

class Agentachievementtop extends Model
{
	protected $table = 'agent_achievement_top';
	
	//定义缓存时间
	protected $cache_time=1800;
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
	
	//获取各等级总算
	public function getLevelInfo()
	{
		$res=array();
		
		$memKey='getLevelInfo';
		$res=Cache::get($memKey);
		
		if(empty($res)) {
			$agent=model('Agents');
			$level=model('Agentlevel');
			$levelList=$level->order('id asc')->select();
			
			$i=0;
			foreach($levelList as $k=>$v)
			{
				$res[$i]['name']=$v['name'];
				$res[$i]['count']=$agent->where('role='.$v['id'].' and is_del=0')->count();
				$i++;
			}
			
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		return $res;
	}
	
	//获取代理相关信息
	public function getAgentsInfo($day,$month)
	{
		$res=array();
		
		$memKey='getAgentsInfo_'.$day;
		$res=Cache::get($memKey);
		
		if(empty($res)) {
			$agent=model('Agents');
		 
			$res['total']=$agent->where('is_del=0 and role>0')->count();
			$res['reg']=$agent->where('is_del=0 and status in(1,2)')->count();
			$res['promote']=$agent->where('is_del=0 and status=2')->count();
			$res['plus']=$agent->where(" is_del=0 and role>0 and DATE_FORMAT(create_ctime,'%d')=".$day." and DATE_FORMAT(create_ctime,'%m')=".$month)->count();
		 
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		return $res;
		
	}
	
	//获取产品统计相关信息//出售中的商品数//库存中的商品数
	public function getProdctInfo()
	{
		$res=array();
	
		$memKey='getProdctInfo';
		$res=Cache::get($memKey);
	
		if(empty($res)) {
			$product=model('Productmanagement');
				
			$res['sale']=$product->where('state=1')->count();
			$res['stock']=$product->where('state=0')->count();
	
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		return $res;
	
	}
	
	//获取总计的很多订单
	public function getTotalOrders($month)
	{
		$res=array();
		
		$memKey='getTotalOrders_'.$month;
		$res=Cache::get($memKey);
		
		if(empty($res)) {

			$order=model('Agentorders');
		
			//总订单数
			$res['totalOrders']=$order->where('order_status in(4,5)')->group('order_number')->count();
 
			//总销售额
			$tmp=Db::query("SELECT SUM(order_amount_pay) as total FROM (SELECT order_amount_pay FROM `agent_orders` WHERE ( order_status IN(4,5) ) GROUP BY order_number) AS t");
			$res['totalAmount']=$tmp[0]['total'];
			
			//本月订单
			$res['currentOrders']=$order->where("order_status in(4,5) and DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$month)->group('order_number')->count();
			//本月销售额
			$res['currentAmount']=$order->where("order_status in(4,5) and DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$month)->sum('order_amount_pay');
		
			//本月销售额
			$tmp=Db::query("SELECT SUM(order_amount_pay) as total FROM (SELECT order_amount_pay FROM `agent_orders` WHERE ( order_status IN(4,5) AND DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$month." ) GROUP BY order_number) AS t");
			$res['currentAmount']=$tmp[0]['total'];
			
			//待发货订单
			$res['waitOrders']=$order->where('order_status=2')->group('order_number')->count();
			//已发货订单
			$res['alreadyOrders']=$order->where('order_status=3')->group('order_number')->count();
			//已完成订单status
			$res['completeOrders']=$order->where('order_status in(4,5)')->group('order_number')->count();
			//代理商订单
			$res['agentOrders']=$order->where('order_status in(4,5) and delivery_agent_id >1')->group('order_number')->count();
		
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		return $res;
		
	}
	
	//获取最近7天的数据信息
	public function getSevenInfo()
	{
		$res=array();
		
		$memKey='getSevenInfo';
		$res=Cache::get($memKey);
		
		if(empty($res)) {
			$order=model('Agentorders');
		
			//近7日订单笔数,近7日订单金额
			for($i = 0; $i < 7; $i++){
				$cday=date('d', strtotime('-'.$i.' day'));
				$cmonth=date('m', strtotime('-'.$i.' day'));
		 
				$res['ordersCount'][$i]['month']=$cmonth;
				$res['ordersCount'][$i]['day']=$cday;
				$res['ordersCount'][$i]['value']=$order->where("order_status in(4,5) and DATE_FORMAT(FROM_UNIXTIME(create_time),'%d')=".$cday." and DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$cmonth)->group('order_number')->count();
			 
				
				$res['ordersAmount'][$i]['month']=$cmonth;
				$res['ordersAmount'][$i]['day']=$cday;
				$res['ordersAmount'][$i]['value']=$order->where("order_status in(4,5) and DATE_FORMAT(FROM_UNIXTIME(create_time),'%d')=".$cday." and DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$cmonth)->sum('order_amount_pay');
		 
			}
 
		
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		return $res;
	}
	
	//获取总奖励数据
	public function getTotalRewardInfo()
	{
		$res=array();
		
		$memKey='getTotalRewardInfo';
		$res=Cache::get($memKey);
		
		if(empty($res)) {
			$res['recommendRatio']=$res['saleRatio']=$res['performanceRatio']=0;
			$reward=model('Agentorderreward');
			$performance=model('Agentperformancereward');
			$giftReward=model('Promotiongiftorderreward');
		
			$totalReward['recommend']=$reward->where('reward_type=1 and status=2')->sum('recommend_reward');
			$totalReward['directsaleReward']=$reward->where('reward_type=2 and status=2')->sum('directsale_reward');
			$totalReward['wholesaleReward']=$reward->where('reward_type=3 and status=2')->sum('wholesale_reward');
			$totalReward['performanceReward']=$performance->where('status=2')->sum('performance_profit');
			$totalReward['giftReward']=$giftReward->where('reward_type=1 and status=2')->sum('recommend_reward');
		
			//总收益
			$res['totalProfit']=$totalReward['recommend']+$totalReward['directsaleReward']+$totalReward['wholesaleReward']+$totalReward['performanceReward']+$totalReward['giftReward'];
		
			$res['giftRewardRatio']=$res['performanceRatio']=$res['saleRatio']=$res['recommendRatio']=0;
			$res['totalProfit']>0 && $res['recommendRatio']=round($totalReward['recommend']/$res['totalProfit']*100,2);
			$res['totalProfit']>0 && $res['saleRatio']=round(($totalReward['directsaleReward']+$totalReward['wholesaleReward'])/$res['totalProfit']*100,2);
			$res['totalProfit']>0 && $res['performanceRatio']=round($totalReward['performanceReward']/$res['totalProfit']*100,2);
			$res['totalProfit']>0 && $res['giftRewardRatio']=round($totalReward['giftReward']/$res['totalProfit']*100,2);
		
			!empty($res) && Cache::set($memKey,$res,$this->cache_time);
		}
		return $res;
	}
	
	//订单金额统计
	public function getOrderAmountTop($month,$year)
	{
		$res=array();
		
		$memKey='getOrderAmountTop_'.$month.'_'.$year;
		$res=Cache::get($memKey);
		
		if(empty($res)) {
		
			$order=model('Agentorders');
			$agent=model('Agents');
			//校验下是否已经发放
			$count=0;
			$count=$this->where('month='.$month.' and year='.$year.' and type=4')->count();
			if($count==0) {
				
				$agentList=$order->where("delivery_agent_id<=1 and order_amount_pay>0 and order_status in(4,5) and DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$month)->field('sum(order_amount_pay) AS total,agent_id')->group('agent_id')->order('total desc')->limit(10)->select();
		 
				$i=1;
				foreach($agentList as $k=>$v)
				{
					$data=array();
					$data['agent_id']=$agent->getAgentsInviter($v['agent_id']);		//出货时下单人的上级
					if($data['agent_id']) {
						
						$data['profit']=$v['total'];
						$data['rank']=$i;
						$data['create_time']=time();
						$data['year']=$year;
						$data['month']=$month;
						$data['type']=4;
			
						//tp的BUG 第二次开始要手工指定主键ID
						isset($this->id) && $data['id']=$this->id+1;
						/*同一个实例多次添加，需要设置isupdate*/
						$this->isUpdate(false)->save($data);
					}
		
					$i++;
				}
			}
			$res=$this->where('month='.$month.' and year='.$year.' and type=4')->select();
			!empty($res) &&  Cache::set($memKey,$res,$this->cache_time);
		}
		
		return $res;
	}
	
	//获取一个月推荐的人数排行
	public function getRecommendTop($month,$year)
	{
		$res=array();
		
		$memKey='getRecommendTop_'.$month.'_'.$year;
		//$res=Cache::get($memKey);
		
		if(empty($res)) {
		
			$agent=model('Agents');
			//校验下是否已经发放
			$count=0;
			$count=$this->where('month='.$month.' and year='.$year.' and type=3')->count();
			if($count==0) {
				$agentList=$agent->where("inviter>0 and role>0 and DATE_FORMAT(create_ctime,'%m')=".$month)->field('COUNT(agent_id) AS total,inviter')->group('inviter')->order('total desc')->limit(10)->select();
				$i=1;
				foreach($agentList as $k=>$v)
				{
					$data=array();
					$data['agent_id']=$v['inviter'];
					$data['profit']=$v['total'];
					$data['rank']=$i;
					$data['create_time']=time();
					$data['year']=$year;
					$data['month']=$month;
					$data['type']=3;
						
					//tp的BUG 第二次开始要手工指定主键ID
					isset($this->id) && $data['id']=$this->id+1;
					/*同一个实例多次添加，需要设置isupdate*/
					$this->isUpdate(false)->save($data);
						
					$i++;
				}
			}
			$res=$this->where('month='.$month.' and year='.$year.' and type=3')->select();
			!empty($res) &&  Cache::set($memKey,$res,$this->cache_time);
		}
		
		return $res;
	}
	
	//获取总收益排行
	public function getProfitReward($month,$year)
	{
		$res=array();
	
		$memKey='randProfitReward_'.$month.'_'.$year;
		$res=Cache::get($memKey);
	
		if(empty($res)) {
	
			$profit=model('Agentprofit');
			//校验下是否已经发放
			$count=$this->where('month='.$month.' and year='.$year.' and type=1')->count();
			if($count==0) {
				$profitList=$profit->where("DATE_FORMAT(FROM_UNIXTIME(create_time),'%m')=".$month)->field('sum(profit) as total,agent_id')->group('agent_id')->order('total desc')->limit(10)->select();
				$i=1;
				foreach($profitList as $k=>$v)
				{
					$data=array();
					$data['agent_id']=$v['agent_id'];
					$data['profit']=$v['total'];
					$data['rank']=$i;
					$data['create_time']=time();
					$data['year']=$year;
					$data['month']=$month;
					$data['type']=1;
					
					//tp的BUG 第二次开始要手工指定主键ID
					isset($this->id) && $data['id']=$this->id+1;
					/*同一个实例多次添加，需要设置isupdate*/
					$this->isUpdate(false)->save($data);
					
					$i++;
				}
			}
			$res=$this->where('month='.$month.' and year='.$year.' and type=1')->select();
			!empty($res) &&  Cache::set($memKey,$res,$this->cache_time);
		}
	
		return $res;
	}
	
	//获取绩效奖励排行
	public function getPerformanceReward($month,$year)
	{
		$res=array();
	
		$memKey='randPerformanceReward_'.$month.'_'.$year;
		$res=Cache::get($memKey);
	
		if(empty($res)) {
	
			$performance=model('Agentperformancereward');
			
			$count=$this->where('month='.$month.' and year='.$year.' and type=2')->count();
			if($count==0) {
				$performanceList=$performance->where('status=2 and month='.$month)->group('agent_id')->field('sum(performance_profit) as total,agent_id')->order('total','desc')->limit(10)->select();
				$i=1;
				foreach($performanceList as $k=>$v)
				{
					$data=array();
					$data['agent_id']=$v['agent_id'];
					$data['profit']=$v['total'];
					$data['rank']=$i;
					$data['create_time']=time();
					$data['year']=$year;
					$data['month']=$month;
					$data['type']=2;
					

					//tp的BUG 第二次开始要手工指定主键ID
					isset($this->id) && $data['id']=$this->id+1;
					/*同一个实例多次添加，需要设置isupdate*/
					$this->isUpdate(false)->save($data);
					
					$i++;
				}
			}
			$res=$this->where('month='.$month.' and year='.$year.' and type=2')->select();
			!empty($res) &&  Cache::set($memKey,$res,$this->cache_time);
		}
	
		return $res;
	}
	 
	
}
