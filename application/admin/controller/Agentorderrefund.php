<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentorderrefund extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$request=request();
    	 
    	$agent_id=$name=$contact=$role=$order_number=$refund_status=$refund_paytype=$auth_status=$refund_type=0;
    	 
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	 
    	 
    	$request->param('auth_status') && $auth_status=$request->param('auth_status');
    	$request->param('refund_status') && $refund_status=$request->param('refund_status');
    	$request->param('refund_paytype') && $refund_paytype=$request->param('refund_paytype');
    	$request->param('refund_type') && $refund_type=$request->param('refund_type');
    	 
    	$data=$adata=array();
    	 
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];;
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($role) && $adata['role']=$role;
    	!empty($order_number) && $data['order_number']=['like','%'.$order_number.'%'];
    	!empty($refund_status) && $data['refund_status']=$refund_status;
    	!empty($auth_status) && $data['auth_status']=$auth_status;
    	!empty($refund_paytype) && $data['refund_pay_type']=$refund_paytype;
    	!empty($refund_type) && $data['refund_type']=$refund_type;
  
    	
    	$refund=model('Agentorderrefund');
    	
    	$refundList=$refund->hasWhere('Agents',$adata)
    	->where($data)
    	->order('id', 'desc')
    	->paginate(config('paginate.list_rows'));
    	$this->assign('refundList',$refundList);
    	
    	//角色等级列表
    	$level=model('Agentlevel');
    	$roleList=$level->order('id', 'desc')->select();
    	$this->assign('roleList',$roleList);
    	
    	//退款状态
    	$refundStatusList=$refund->refundStatus;
    	$this->assign('refundStatusList',$refundStatusList);
    	
    	//审核状态
    	$authStatusList=$refund->authStatus;
    	$this->assign('authStatusList',$authStatusList);
    	
    	//支付方式
    	$refundPayTypeList=$refund->refundPayType;
    	$this->assign('refundPayTypeList',$refundPayTypeList);
    	
    	//退款类型
    	$refundTypeList=$refund->refundType;
    	$this->assign('refundTypeList',$refundTypeList);
    	
    	//搜索条件赋值
    	$this->assign('agent_id',$agent_id);
    	$this->assign('name',$name);
    	$this->assign('role',$role);
    	$this->assign('contact',$contact);
    	$this->assign('order_number',$order_number);
    	$this->assign('refund_status',$refund_status);
    	$this->assign('auth_status',$auth_status);
    	$this->assign('refund_paytype',$refund_paytype);
    	$this->assign('refund_type',$refund_type);
    	
    	return $this->fetch();
    }


    /**
     * 审核
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function auth(Request $request)
    {
        $order_number=$request->param('id');
        $auth_status=$request->param('auth_status');
        $reason=$request->param('reason');
        
        $res=0;
 
        //通过
        if($order_number && $auth_status==1)
        {
     
        	$refund=model('Agentorderrefund');
        	$refundInfo=$refund->where(array('order_number'=>$order_number))->find();
 
        	$data=array();
        	$data['auth_time']=time();
        	$data['auth_status']=2;
        	$data['author']=session('username');
        	$data['reason']=$reason;
        	$res=$refund->save($data,array('order_number'=>$order_number,'auth_status'=>1));
 
        	//如果支付方式是1 那么调用微信支付退款借口
        	if($refundInfo->getData('refund_pay_type')==1) {
	        	//调用微信支付的退款接口
	        	$pay = controller('index/Pay', 'controller');
	        	$pay->refund($order_number);
	        	
        	} elseif($refundInfo->getData('refund_pay_type')==3) {
        		//库存支付
        		agent_order_stock_pay_refund($order_number,$refundInfo->getData('type'));
        		//如果是库存支付，那么直接退款成功
        		$refund->save(array('refund_status'=>2,'refund_time'=>time()),array('order_number'=>$order_number,'auth_status'=>2));
        	
        	} elseif($refundInfo->getData('refund_pay_type')==2) {
        		//如果是线下支付，那么直接退款成功
        		$refund->save(array('refund_status'=>2,'refund_time'=>time()),array('order_number'=>$order_number,'auth_status'=>2));
        	}
     
        	//获取消息发送人的手机号
        	$agentInfo=model('admin/Agents')->find($refundInfo['agent_id']);
        	
        	//发个消息退款成功
        	$mdata['first']='您的订单已成功退款！';
        	$mdata['account']=$agentInfo['phone'];
        	$mdata['time']=date('Y-m-d H:i:s');
        	$mdata['type']='订单退款';
        	$mdata['remark']='您的订单已成功退款，订单号是'.$order_number;
        	
        	//发送通知
        	message_notification(3,$refundInfo['agent_id'],$mdata);
        	
        	$res=1;
        }
        
    	if($res==0){
    		return json_encode(['code'=>'-1','msg'=>'操作失败']);
    	}else{
    		return json_encode(['code'=>'0','msg'=>'操作成功']);
    	}
        
    }
    
    //审核拒绝
    public function reason()
    {
    	return $this->fetch();
    }
    
    //保存审核拒绝
    public function saveReason(Request $request)
    {
    	$order_number=$request->param('id');
    	$reason=$request->param('reason');
    	
    	$refund=model('Agentorderrefund');
    	$refundInfo=$refund->where(array('order_number'=>$order_number,'auth_status'=>1))->find();
    	
    	$data=array();
    	$data['auth_time']=time();
    	$data['auth_status']=3;
    	$data['author']=session('username');
    	$data['reason']=$reason;
    	$res=$refund->save($data,array('order_number'=>$order_number,'auth_status'=>1));
    	
    	
    	//获取消息发送人的手机号
    	$agentInfo=model('admin/Agents')->find($refundInfo['agent_id']);
    	 
    	//发个消息退款成功
    	$mdata['first']='您的订单退款失败！';
    	$mdata['account']=$agentInfo['phone'];
    	$mdata['time']=date('Y-m-d H:i:s');
    	$mdata['type']='订单退款';
    	$mdata['remark']='您的订单'.$order_number.'.退款失败，理由是'.$reason;
    	 
    	//发送通知
    	message_notification(3,$refundInfo['agent_id'],$mdata);
    	
    	if($res==0){
    		return json_encode(['code'=>'-1','msg'=>'操作失败']);
    	}else{
    		return json_encode(['code'=>'0','msg'=>'操作成功']);
    	}
    	
    }
    
    public function excelIndex()
    {
    	$request=request();
    	
    	$agent_id=$name=$contact=$role=$order_number=$refund_status=$auth_status=0;
    	
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	
    	
    	$request->param('auth_status') && $auth_status=$request->param('auth_status');
    	$request->param('refund_status') && $refund_status=$request->param('refund_status');
    	
    	$data=$adata=array();
    	
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];;
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($role) && $adata['role']=$role;
    	!empty($order_number) && $data['order_number']=['like','%'.$order_number.'%'];
    	!empty($refund_status) && $data['refund_status']=$refund_status;
    	!empty($auth_status) && $data['auth_status']=$auth_status;
    	
    	 
    	$refund=model('Agentorderrefund');
    	 
    	$refundList=$refund->hasWhere('Agents',$adata)
    	->where($data)
    	->order('id', 'desc')
    	->select();
    	
    	$filename='退款申请记录';
    	
    	//开始导出相关
    	$title=array('代理商ID','姓名','联系方式','角色','订单编号','订单类型','支付方式','退款金额','申请日期','退款状态','审核状态');
    	
    	//循环输出
    	$data=array();
    	foreach($refundList as $k=>$v) 
    	{
    		$data[$k]['agent_id']=$v->agent_id;
    		$data[$k]['name']=$v->agents->name;
    		$data[$k]['contact']='手机号:'.$v->agents->phone." 微信号:".$v->agents->wechat;
    		$data[$k]['rolename']=$v->agents->generation.'代'.get_reward_levelname($v->agents->role);
    		
    		$data[$k]['order_number']='订单编号:'.$v->order_number;
    		$data[$k]['type']=$v->type;
    		$data[$k]['refund_pay_type']=$v->refund_pay_type;
    		$data[$k]['refund_fee']=$v->refund_fee;
    		$data[$k]['create_time']=$v->create_time;
    		
    		$data[$k]['refund_status']=$v->refund_status;
    		$data[$k]['auth_status']=$v->auth_status;
    		
    	}
    	
    	$this->exportexcel($data,$title,$filename);
    	
    }
  
    //微信退款结果查看
    public function weixinPayRefund()
    {
    	$request=request();
    	$order_number=$request->param('order_number');
 
    	$result=array();
    	//调用微信支付的退款接口
    	$pay = controller('index/Pay', 'controller');
    	$result=$pay->refundQuery($order_number);
    	
    	//描述
    	$errAry=model('admin/Agentorderrefund')->errorStatus;
    	$result['err_code'] && $result['des']=$errAry[$result['err_code']];
 
    	
    	$this->assign('resultInfo',$result);
    	$this->assign('order_number',$order_number);
    	
    	return $this->fetch('weixinPayRefund');
    }
    
    //微信支付结果查看
    public function weixinPayInfo()
    {
    	$request=request();
    	$order_number=$request->param('order_number');
    	
    	$result=array();
    	//调用微信支付的退款接口
    	$pay = controller('index/Pay', 'controller');
    	$result=$pay->payQuery($order_number);
    	 
    	 
    	$this->assign('resultInfo',$result);
    	$this->assign('order_number',$order_number);
    }
}
