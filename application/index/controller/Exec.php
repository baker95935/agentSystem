<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Exec extends Controller
{
    /**
     * 自动执行脚本-默认是每20分钟执行一次
     *
     * @return \think\Response
     */
    public function toExec()
    {
    	//默认是每20分钟执行一次
    	
        //自动收货脚本
        $this->autoCompleteOrder();
        
        //自动统计要降级的用户
        //$this->autoCountStockLog();
        
        //符合条件的要降级的自动降级
        //$this->autoDownRole();
        
    }
    
    /**
     * 每天执行1次0点0分
     */
    public function day()
    {
    	count_all_agent_data_for_rank();//各种排行统计
    	
    }
    
    //每天八点执行
    public function eightClock()
    {
    	//库存为0时提醒充值
    	//$this->message_info_notice();
    }

    //订单自动完成
    private function autoCompleteOrder()
    {
    	$order=model('Orders');
    	$setting=model('Agentordersetting');
    	
    	require_once '../vendor/weixin/log.php';
    	
    	//初始化日志
    	$logHandler= new \CLogFileHandler("../logs/autorun/".date('Y-m-d').'.log');
    	$log = \Log::Init($logHandler, 15);
    	
    	$settingInfo=$setting->find();
    	if($settingInfo['auto_confirm_time']) {
    		
    		$time=$settingInfo['auto_confirm_time']*24*60*60;//几天的秒数
    		$now=time();
    		
    		//获取所有已发货的订单，符合条件的放到数组里面
    		$orderList=$order->where('order_status=3 and delivery_time>0')->group('order_number')->field('order_number,delivery_time')->select();
    		
    		$log->INFO($order->getLastSql());
    		
    		$result=array();
    		foreach($orderList as $k=>$v) {
    			if($now>$v['delivery_time']+$time) {
    				$result[]=$v['order_number'];
    			}
    		}
    		
    		//结果数组去重，因为多个产品是一个订单号
    		$result=array_unique($result);
    		
    		if(!empty($result)) {
	    		$data=array();
	    		$data['order_number']=['in',$result];
	    		$realOrderList=$order->where($data)->group('order_number')->field('order_number,order_status')->select();
	     		$log->INFO($order->getLastSql());
	     		
	     		$i=0;
	    		foreach($realOrderList as $k=>$v)
	    		{
	    			//保持订单状态
	    			$ndata=array();
	    			$ndata['order_status']=4;
	    			$ndata['commplete_time']=time();
	    			$order->update($ndata,array('order_number'=>$v['order_number'],'order_status'=>3));
	    	
	    			//奖励发放
	    			provide_order_reward($v['order_number']);
	    			$i++;
	    		}
	    		$log->INFO($i);
    		}
    		$log->INFO('ok!');
    	}
    }
    
    //自动统计库存低于最低库存的放到记录表
    private function autoCountStockLog()
    {
    	//思路整理，查找每个等级对应的最低库存，然后和数据库里面的代理商 等级对比，低于的放到数据库里面
    	$agency=model('admin/Agentrewardagency');
    	$agencyList=array();
    	$agencyList=$agency->field('role,lowest_limit')->select();
    	
    	//放到数组里面 方便调用
    	$resAry=array();
    	foreach($agencyList as $k=>$v) {
    		$resAry[$v['role']]=$v['lowest_limit'];
    	}
    	
    	$stockLog=model('admin/Agentlowerstocklog');
     
    	
    	$agent=model('admin/Agents');
    	$agentList=$agent->field('role,stock_money,agent_id')->where('role>0 and is_del=0')->select();
    	
    	foreach($agentList as $k=>$v) {
    		if($resAry[$v['role']]>$v['stock_money']) {
    			
    			//如果库存比最低库存少，那么放到表里面
    			//校验下 是否有
    			$count=$stockLog->where('role='.$v['role'].' and status=1 and agent_id='.$v['agent_id'])->count();
    			if($count==0) {
	    			$data=array();
	    			$data['create_time']=time();
	    			$data['agent_id']=$v['agent_id'];
	    			$data['status']=1;
	    			$data['role']=$v['role'];
	    			$data['down_time']=time()+config('web.down_time')*24*60*60;
	    			
	    			//tp的BUG 第二次开始要手工指定主键ID
	    			isset($stockLog->id) && $data['id']=$stockLog->id+1;
	    			/*同一个实例多次添加，需要设置isupdate*/
	    			$stockLog->isUpdate(false)->save($data);
    			}
    			
    		} else {
    			
    			//如果库存大于要求，那么校验下是否有记录
    			$count=$stockLog->where('role='.$v['role'].' and status=1 and agent_id='.$v['agent_id'])->count();
    			if($count>0) {
    				$ndata=array();
    				$ndata['update_time']=time();
    				$ndata['status']=3;
    				$ndata['remark']='库存充足，降级失败';
    				$stockLog->update($ndata,array('role'=>$v['role'],'status'=>1,'agent_id'=>$v['agent_id']));
    			}
    		}
    	}
    	
     
    	
    }
    
    //自动降级
    private function autoDownRole()
    {	
    	$stockLog=model('admin/Agentlowerstocklog');
    	$change=model('admin/Agentchangerole');
    	$agent=model('admin/Agents');
    	
    	$now=time();
    	
    	//思路整理，获取库存低于记录的表，找到降级时间，如果大于当前时间，那么降级
    	$stockLogList=$stockLog->where('status=1 and down_time<'.$now)->select();
 
    	foreach($stockLogList as $k=>$v) {
    		if($v['down_time']<$now) {
    			
    			//存到变更记录
    			$tmp=$agent->find($v['agent_id']);
    			//判断下  如果记录的等级和当前等级一致，那么降级
    			if($v['role']==$tmp['role']) {
	    			$data=array();
	    			$data['create_time']=time();
	    			$data['before_role']=$tmp['role'];
	    			$data['after_role']=$tmp['role']-1;
	    			$data['agent_id']=$v['agent_id'];
	    			
	    			//tp的BUG 第二次开始要手工指定主键ID
	    			isset($change->id) && $data['id']=$change->id+1;
	    			/*同一个实例多次添加，需要设置isupdate*/
	    			$change->isUpdate(false)->save($data);
	    		 
	    			$change_id=$change->id;
	    			
	    			//变更记录表的状态
	    			$ndata=array();
	    			$ndata['status']=2;
	    			$ndata['update_time']=time();
	    			$ndata['change_id']=$change_id;
	    			$stockLog->update($ndata,array('id'=>$v['id']));
	    			
	    			//降级
	        		$agent->where('agent_id',$v['agent_id'])->update(['role'=>$tmp['role']-1,'is_use'=>1,'status'=>3]);
	        		
	        		//那么发个消息通知
	        		$mdata=array();
	        		$mdata['first']='您的身份已经变更！';
	        		$mdata['account']=$tmp['phone'];
	        		$mdata['time']=date('Y-m-d H:i:s');
	        		$mdata['type']='身份变更';
	        		$mdata['remark']='因您的库存不足，现降低您的身份，请重新登录账户查询';
	        		
	        		//给下单人发送发货通知
	        		message_notification(3,$v['agent_id'],$mdata);
    			} else {
    				
    				$ndata=array();
    				$ndata['status']=3;
    				$ndata['update_time']=time();
    				$ndata['remark']='用户角色变动，降级失败';
    				$stockLog->update($ndata,array('id'=>$v['id']));
    				
    			}
    			
    		}
    	}
    }
    
    //库存为0时提醒充值
    private  function message_info_notice()
    {
    	$agent=model('Agents');
    	$agentList=$agent->where('role>0 and stock_money<1 ')->field('agent_id,phone')->select();
    	
    	foreach($agentList as $k=>$v)
    	{
    		//那么发个消息通知
    		$mdata=array();
    		$mdata['first']='您的库存余额不足！';
    		$mdata['account']=$v['phone'];
    		$mdata['time']=date('Y-m-d H:i:s');
    		$mdata['type']='库存不足';
    		$mdata['remark']='您的库存余额不足为了不影响您的收益到账请尽快充值';
    		 
    		//给下单人发送发货通知
    		message_notification(3,$v['agent_id'],$mdata);
    	}
    }
    
 
}
