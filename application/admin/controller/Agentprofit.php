<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentprofit extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$profit=model('Agentprofit');
    	
    	$request=request();
    	
    	$agent_id=$name=$contact=$role=$order_number=$order_stime=$order_etime=$type=$status=0;
    	
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	$request->param('order_stime') && $order_stime=$request->param('order_stime');
    	$request->param('order_etime') && $order_etime=$request->param('order_etime');
    	$request->param('type') && $type=$request->param('type');
    	
    	$request->param('status') && $status=$request->param('status');//代理收入的销售额和奖励
    	
    	$data=$adata=array();
    	
		!empty($agent_id) && $adata['agent_id']=$agent_id;
		!empty($name) && $adata['name']=['like','%'.$name.'%'];;
		!empty($contact) && $adata['phone|wechat']=$contact;
		!empty($role) && $adata['role']=$role;
		if($type==1) {
			$data['type']=['in',array(1,2,7,8)];
		} else {
			$data['type']=$type;
		}
		
		!empty($order_number) && $data['order_number']=$order_number;
		
		$keyname="agent_orders.create_time";
		$data['type']==5 && $keyname="promotion_gift_order.create_time";
	 
		
    	//1直销奖励2间接奖励3招商奖励4绩效奖励5礼包奖
    	
    	$data['type']!=5 &&$profitList=$profit
	    	->where($data)
	    	->order('id', 'desc')
	    	->distinct(true)
	    	->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
    	
    	//礼包奖励表
    	$data['type']==5 && $profitList=$profit->hasWhere('Agents',$adata)
    		->join('promotion_gift_order','promotion_gift_order.order_number=Agentprofit.order_number')
    		->where($data)
    		->order('id', 'desc')
    		->distinct(true)
    		->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
    	
			
		//echo Model('agent_profit')->getLastSql();
    	$this->assign('profitList',$profitList);
 
    	//角色等级列表
    	$level=model('Agentlevel');
    	$roleList=$level->order('id', 'desc')->select();
    	$this->assign('roleList',$roleList);
    	
    	//得到页面标题
    	$title='代理收益';
    	$status==2 && $title='代理销售额';
    	$data['type']==3 && $title='招商奖励';
    	$data['type']==5 && $title='礼包奖励';
    	$data['type']==6 && $title='直销奖励';
    	$this->assign('title',$title);
    	
    	//搜索条件赋值
    	$this->assign('agent_id',$agent_id);
    	$this->assign('name',$name);
    	$this->assign('role',$role);
    	$this->assign('contact',$contact);
    	$this->assign('order_number',$order_number);
    	$this->assign('order_stime',$order_stime);
    	$this->assign('order_etime',$order_etime);
    	$this->assign('type',$type);
    	$this->assign('status',$status);
    	
    	return $this->fetch();
    }
 
    //导出
    public function excelIndex()
    {
    	$profit=model('Agentprofit');
    	 
    	$request=request();
    	 
    	$agent_id=$name=$contact=$role=$order_number=$order_stime=$order_etime=$type=$status=0;
    	 
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	$request->param('order_stime') && $order_stime=$request->param('order_stime');
    	$request->param('order_etime') && $order_etime=$request->param('order_etime');
    	$request->param('type') && $type=$request->param('type');
    	
    	$request->param('status') && $status=$request->param('status');//代理收入的销售额和奖励
 
    	
    	$data=$adata=array();
    	 
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];;
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($role) && $adata['role']=$role;
    	if($type==1) {
    		$data['type']=['in',array(1,2)];
    	} else {
    		$data['type']=$type;
    	}
    	
    	!empty($order_number) && $data['agent_orders.order_number']=$order_number;
    	//time
    	$order_stime>0 && $data['agent_orders.create_time']=['>=',strtotime($order_stime)];
    	if($order_etime>0){
    		$tmp_time=strtotime($order_etime)+60*60*24;
    		$data['agent_orders.create_time']=['<=',$tmp_time];
    	}
    		
    	if(!empty($order_stime) && !empty($order_etime)) {
    		$tmp_etime=strtotime($order_etime)+60*60*24;
    		$data['agent_orders.create_time']=['between time',[$order_stime,$tmp_etime]];
    	}
    	
    	//1直销奖励2间接奖励3招商奖励4绩效奖励5礼包奖
    	$profitList=$profit->hasWhere('Agents',$adata)
    	->join('agent_orders','agent_orders.order_number=Agentprofit.order_number')
    	->where($data)
    	->order('id', 'desc')
    	->select();
    	
    	//礼包奖励表
    	if($data['type']==5) {
    		$profitList=$profit->hasWhere('Agents',$adata)
    		->join('promotion_gift_order','promotion_gift_order.order_number=Agentprofit.order_number')
    		->where($data)
    		->order('id', 'desc')
    		->select();
    	}
    	
    	
    	$filename='代理收益记录';
    	$status==2 && $filename='代理销售额记录';
    	$data['type']==3 && $filename='招商奖励记录';
    	$data['type']==5 && $filename='礼包奖励记录';
    	
    	$name='代理收益';
    	$type==3 && $name='招商奖励';
    	$type==5 && $name='礼包奖励';
    	$status==2 &&$name="代理销售额";
    	//开始导出相关
    	$title=array('代理商ID','姓名','联系方式','身份','订单编号',$name,'下单日期');
    	
    	//循环输出
    	$data=array();
    	foreach($profitList as $k=>$v)
    	{
    		$data[$k]['agent_id']=$v->agent_id;
    		$data[$k]['name']=$v->agents->name;
    		$data[$k]['contact']='手机号:'.$v->agents->phone." 微信号:".$v->agents->wechat;
    		$data[$k]['rolename']=$v->agents->generation.'代'.get_reward_levelname($v->agents->role);
    		
    		$data[$k]['order_number']='订单编号:'.$v->order_number;
    		$tmp=' ';
    		$v->type==1 &&	$tmp='直销';
    		$v->type==2 &&	$tmp='间接';
    		
    		$data[$k]['profit']=$tmp." ".$v->profit;
    		
    		$status==2 && $data[$k]['profit']=$v->sales_amount;
    		
    		if($type==5) {
    			$data[$k]['create_time']=$v->giftOrders->create_time;
    		}  else {
    			$data[$k]['create_time']=$v->orders->create_time;
    		}
    	}
    	
    	$this->exportexcel($data,$title,$filename);
    	
    }
}
