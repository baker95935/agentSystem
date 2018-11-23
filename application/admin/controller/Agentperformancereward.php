<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentperformancereward extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$performance=model('Agentperformancereward');
    	
    	$request=request();

    	$agent_id=$name=$contact=$role=$month=$status=0;
    	
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('month') && $month=$request->param('month');
    	$request->param('status') && $status=$request->param('status');
    	
    	$data=$adata=array();
    	!empty($month) && $data['month']=$month;
    	!empty($status) && $data['Agentperformancereward.status']=$status;
    	
    	
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($role) && $adata['role']=$role;
    	
    	
    	$performanceList=$performance->hasWhere('Agents',$adata)
    	->where($data)->order('id','desc')->paginate(config('paginate.list_rows'));
    	
    	
    	$this->assign('performanceList',$performanceList);
    	
    	//角色等级列表
    	$level=model('Agentlevel');
    	$roleList=$level->order('id', 'desc')->select();
    	$this->assign('roleList',$roleList);
    	
    	//月份数组
    	$monthAry=array(1,2,3,4,5,6,7,8,9,10,11,12);
    	$rewardStatus=$performance->rewardStatus;
    	
    	$this->assign('rewardStatus',$rewardStatus);
    	$this->assign('monthAry',$monthAry);
    	

    	//搜索条件赋值
    	$this->assign('agent_id',$agent_id);
    	$this->assign('name',$name);
    	$this->assign('role',$role);
    	$this->assign('contact',$contact);
    	$this->assign('month',$month);
    	$this->assign('status',$status);
    	
    	return $this->fetch();
    }
    
    //导出
    public function excelIndex()
    {
    	$performance=model('Agentperformancereward');
    	 
    	$request=request();
    	
    	$agent_id=$name=$contact=$role=$month=$status=0;
    	 
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('month') && $month=$request->param('month');
    	$request->param('status') && $status=$request->param('status');
    	 
    	$data=$adata=array();
    	!empty($month) && $data['month']=$month;
    	!empty($status) && $data['Agentperformancereward.status']=$status;
    	 
    	 
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($role) && $adata['role']=$role;
    	 
    	 
    	$performanceList=$performance->hasWhere('Agents',$adata)
    	->where($data)->order('id','desc')->select();
    	
    	
    	//开始导出相关
    	$title=array('代理商ID','姓名','联系方式','角色','月份','本月业绩分红','增长系数','上月业绩分红基数','本月业绩分红基数','本月代理收入');
    	$filename='业绩分红记录';
    	
    	$data=array();
    	foreach($performanceList as $k=>$v)
    	{
    		$data[$k]['agent_id']=$v->agent_id;
    		$data[$k]['agent_name']=$v->agents->name;
    		$data[$k]['contact']="手机号：".$v->agents->phone." 微信号：".$v->agents->wechat;
    		$data[$k]['role']=$v->agents->generation."代".get_reward_levelname($v->agents->role);
    		$data[$k]['month']=$v->month;
    		$data[$k]['performance_profit']=$v->performance_profit;
    		$data[$k]['increate_ratio']=$v->increate_ratio;
    		$data[$k]['last_performance_base']=$v->last_performance_base;
    		$data[$k]['current_performance_base']=$v->current_performance_base;
    		$data[$k]['current_agent_profit']=$v->current_agent_profit;
    	}
    	
    	$this->exportexcel($data,$title,$filename);
    }
    
    //结算
    public function balance()
    {
    	$performance=model('Agentperformancereward');
    	
    	$cmonth=date('m',strtotime('-1 month', time()));
    	//$cmonth=date('m');
    	//echo $cmonth;
    	$year=date('Y');
    	$this->assign('year',$year);
    	$this->assign('cmonth',$cmonth);
    	
    	//获取绩效奖励执行天数和是否开启
    	$reward=model('Agentrewardconfig');
    	$rewardInfo=$reward->order('id','desc')->find();
    	$this->assign('rewardInfo',$rewardInfo);
    	
    	
    	//先执行当前月的所有用户的绩效统计,校验下 如果有就不在执行
    	$count=$performance->where(array('year'=>$year,'month'=>$cmonth))->count();
    	if($count==0 && $rewardInfo['valid_performance_reward']==1) {
    		$this->getAgentsCurrentMonthPerformance($year,$cmonth);
    	}
    	
    	$request=request();
    	
    	$agent_id=$name=$contact=$role=$month=$status=0;
    	 
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('month') && $month=$request->param('month');
    	$request->param('status') && $status=$request->param('status');
    	
    	$data=$adata=array();
    	!empty($month) && $data['month']=$month;
    	!empty($status) && $data['Agentperformancereward.status']=$status;
    	
    	
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($role) && $adata['role']=$role;
    	
    	
    	$performanceList=$performance->hasWhere('Agents',$adata)
    	->where($data)->order('id','desc')->paginate(config('paginate.list_rows'));
    	
    	
    	$this->assign('performanceList',$performanceList);
    	
    	//角色等级列表
    	$level=model('Agentlevel');
    	$roleList=$level->order('id', 'desc')->select();
    	$this->assign('roleList',$roleList);
    	
    	//月份数组
    	$monthAry=array(1,2,3,4,5,6,7,8,9,10,11,12);
    	$rewardStatus=$performance->rewardStatus;
    	
    	$this->assign('rewardStatus',$rewardStatus);
    	$this->assign('monthAry',$monthAry);
    	

    	//搜索条件赋值
    	$this->assign('agent_id',$agent_id);
    	$this->assign('name',$name);
    	$this->assign('role',$role);
    	$this->assign('contact',$contact);
    	$this->assign('month',$month);
    	$this->assign('status',$status);
 
    	return $this->fetch();
    }
    
    //分红结算导出
    public function excelBalance()
    {
    	$performance=model('Agentperformancereward');
    	
    	$request=request();
    	
    	$agent_id=$name=$contact=$role=$month=$status=0;
    	 
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('month') && $month=$request->param('month');
    	$request->param('status') && $status=$request->param('status');
    	
    	$data=$adata=array();
    	!empty($month) && $data['month']=$month;
    	!empty($status) && $data['Agentperformancereward.status']=$status;
    	
    	
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($role) && $adata['role']=$role;
    	
    	
    	$performanceList=$performance->hasWhere('Agents',$adata)
    	->where($data)->order('id','desc')->select();
    	
    	//开始导出相关
    	$title=array('代理商ID','姓名','联系方式','角色','月份','状态','本月业绩分红','增长系数','上月业绩分红基数','本月业绩分红基数','本月招商奖励','本月代理收入','本月其他奖励');
   
    	$filename='业绩分红结算';
    	 
    	$data=array();
    	foreach($performanceList as $k=>$v)
    	{
    		$data[$k]['agent_id']=$v->agent_id;
    		$data[$k]['agent_name']=$v->agents->name;
    		$data[$k]['contact']="手机号：".$v->agents->phone." 微信号：".$v->agents->wechat;
    		$data[$k]['role']=$v->agents->generation."代".get_reward_levelname($v->agents->role);
    		$data[$k]['month']=$v->month;
    		$data[$k]['status']=$v['status'];
    		
    		$data[$k]['performance_profit']=$v->performance_profit;
    		$data[$k]['increate_ratio']=$v->increate_ratio;
    		$data[$k]['last_performance_base']=$v->last_performance_base;
    		$data[$k]['current_performance_base']=$v->current_performance_base;
    		$data[$k]['current_recommend_profit']=$v->current_recommend_profit;
    		$data[$k]['current_agent_profit']=$v->current_agent_profit;
    		$data[$k]['current_promotion_gift_profit']=$v->current_promotion_gift_profit;
    	}
    	 
    	$this->exportexcel($data,$title,$filename);
    	
    }

    //获取所有代理商月绩效奖励
    public function getAgentsCurrentMonthPerformance($year,$month)
    {
    	$agent=model('Agents');
    	$userList=$agent->where('role>0 and is_del=0')->field('agent_id')->select();
    	foreach($userList as $k=>$v)
    	{
    		get_agent_performance($year,$month,$v['agent_id']);
    	}
    }

    //发放用户绩效奖励收益
    public function provideAgentPerformanceProfitById()
    {
    	$request=request();
    	$pidAry=array();
    	
    	$pidStr=$request->param('pid/a');
    	$performance=model('Agentperformancereward');
    	 
     
    	$performanceList=$performance->where(['id'=>['in',$pidStr]])->select();
  
    	foreach($performanceList as $k=>$v)
    	{
    		//只有未结算的才能结算
    		if($v->getData('status')==1) {
    			//循环发放奖励
    			provide_performance_profit($v['agent_id'],$v['month'],$v['year']);
    		}
    	}
    	
    	if($request->method()=='GET') {
    		$this->success('操作成功', '/admin/Agentperformancereward/balance/');
    	} else {
    		return 1;
    	}
    	 
    }
    
    //发放绩效奖励收益全部
    public function provideAgentPerformanceProfit()
    {
    	$performance=model('Agentperformancereward');
    	
    	$year=date('Y');
    	$month=date('m');
   
    	$performanceList=$performance->where(array('status'=>1,'year'=>$year,'month'=>$month))->select();
    	
    	foreach($performanceList as $k=>$v)
    	{
    		//循环发放奖励
    		provide_performance_profit($v['agent_id'],$month,$year);
    	}
    	
    }
    
    public function test()
    {
    	echo date('Y-m-d',1531718994);
    }
}
