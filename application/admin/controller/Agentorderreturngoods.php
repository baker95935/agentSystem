<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentorderreturngoods extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$request=request();
    	 
    	$agent_id=$name=$contact=$role=$order_number=$refund_status=$refund_paytype=$auth_status=0;
    	 
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('name') && $name=$request->param('name');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('role') && $role=$request->param('role');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	 
    	 
    	$request->param('auth_status') && $auth_status=$request->param('auth_status');
 
    	 
    	$data=$adata=array();
    	 
    	!empty($agent_id) && $adata['agent_id']=$agent_id;
    	!empty($name) && $adata['name']=['like','%'.$name.'%'];;
    	!empty($contact) && $adata['phone|wechat']=$contact;
    	!empty($role) && $adata['role']=$role;
    	!empty($order_number) && $data['order_number']=['like','%'.$order_number.'%'];
 
    	!empty($refund_paytype) && $data['refund_pay_type']=$refund_paytype;
  
    	
    	$return=model('Agentorderreturngoods');
    	
    	$returnList=$return->hasWhere('Agents',$adata)
    	->where($data)
    	->order('id', 'desc')
    	->paginate(config('paginate.list_rows'));
 
    	$this->assign('returnList',$returnList);
    	
    	//角色等级列表
    	$level=model('Agentlevel');
    	$roleList=$level->order('id', 'desc')->select();
    	$this->assign('roleList',$roleList);
    	
    	//审核状态
    	$authStatusList=$return->authStatus;
    	$this->assign('authStatusList',$authStatusList);
 
    	
    	//搜索条件赋值
    	$this->assign('agent_id',$agent_id);
    	$this->assign('name',$name);
    	$this->assign('role',$role);
    	$this->assign('contact',$contact);
    	$this->assign('order_number',$order_number);
    	$this->assign('auth_status',$auth_status);
 
    	
    	return $this->fetch();
    }


    
    //审核拒绝
    public function reason()
    {
    	return $this->fetch();
    }
    
    //保存审核结果
    public function saveReason(Request $request)
    {
    	$order_number=$request->param('id');
    	$reason=$request->param('reason');
    	$auth_status=$request->param('auth_status');
    	
    	$return=model('Agentorderreturngoods');
    	$returnInfo=$return->where(array('order_number'=>$order_number,'auth_status'=>1))->find();
 
    	
    	//退货审核表
    	$data=array();
    	$data['auth_time']=time();
    	$data['auth_status']=$auth_status;
    	$data['author']=session('username');
    	$data['reason']=$reason;
    	$res=$return->save($data,array('order_number'=>$order_number,'auth_status'=>1));
    	
    	//获取订单信息
    	$order=model('Agentorders');
    	$orderInfo=$order->where("order_number='".$order_number."'")->find();
    		
    	//如果审核通过，那么更改订单状态
    	if($auth_status==2) {
	    	$data=array();
	    	$data['order_status']=7;
	    	$order->save($data,array('order_number'=>$order_number));
    	}
    	
    	//获取消息发送人的手机号
    	$agentInfo=model('admin/Agents')->find($returnInfo['agent_id']);
    	 
    	
    	$returnGoodsMes="退货失败!";
    	$auth_status==2 && $returnGoodsMes='退货成功!';
    	
    	//发个消息退款成功
    	$mdata['first']='您的订单'.$returnGoodsMes;
    	$mdata['account']=$agentInfo['phone'];
    	$mdata['time']=date('Y-m-d H:i:s');
    	$mdata['type']='订单退货';
    	$mdata['remark']='您的订单'.$order_number.$returnGoodsMes.'，理由是'.$reason;
    	 
    	//发送通知
    	message_notification(3,$returnInfo['agent_id'],$mdata);
    	 
    	if($res==0){
    		return json_encode(['code'=>'-1','msg'=>'操作失败']);
    	}else{
    		return json_encode(['code'=>'0','msg'=>'操作成功']);
    	}
    	
    }
    

    //审核拒绝
    public function financialReason()
    {
    	return $this->fetch('financialReason');
    }
    

    //财务审核
 	public function financialAuth(Request $request)
 	{
 		$order_number=$request->param('id');
 		$financial_status=$request->param('financial_status');
 		$financial_reason=$request->param('financial_reason');
 		 
 		$return=model('Agentorderreturngoods');
 		$returnInfo=$return->where(array('order_number'=>$order_number,'auth_status'=>2))->find();
 		
 		$res=0;
 		
 		//如果是退货通过，那么退款给用户
 		if($financial_status==2) {
 		
 			//获取订单信息
 			$order=model('Agentorders');
 			$orderInfo=$order->where("order_number='".$order_number."'")->find();
 		
 			//写入退款记录
 			$refund=model('Agentorderrefund');
 			$ndata=array();
 			$ndata['order_number']=$order_number;
 			$ndata['create_time']=time();
 			$ndata['order_amount_pay']=$orderInfo['order_amount_pay'];
 			$ndata['refund_fee']=$orderInfo['order_amount_pay'];
 			$ndata['agent_id']=$orderInfo['agent_id'];
 			$ndata['type']=$returnInfo->getData('type');
 			$ndata['refund_type']=2;//退货
 		
 			//退款状态获取
 			if($orderInfo->getData('paystyle')==2) {
 				$ndata['refund_pay_type']=1;
 			} else if($orderInfo->getData('paystyle')==1) {
 				$ndata['refund_pay_type']=2;
 			} else if($orderInfo->getData('paystyle')==3) {
 				$ndata['refund_pay_type']=3;
 			}
 		
 			$ndata['auth_time']=time();
 			$ndata['auth_status']=2;
 			$ndata['author']=session('username');
 			$ndata['reason']=$financial_reason;
 		
 			$refundId=$refund->save($ndata);
 		
 		
 			//退款
 			$refundInfo=$refund->where("order_number='".$order_number."'")->find();
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
 		
 			//更新退货表
 			$rdata=array();
 			$rdata['financial_auth']=$financial_status;
 			$rdata['financial_time']=time();
 			$rdata['financial_reason']=$financial_reason;
 			$rdata['financial_author']=session('username');
 			$res=$return->save($rdata,array('order_number'=>$order_number,'auth_status'=>2));
  
 		}	
 		
 	    if($res==0){
    		return json_encode(['code'=>'-1','msg'=>'操作失败']);
    	}else{
    		return json_encode(['code'=>'0','msg'=>'操作成功']);
    	}
 	}
}
