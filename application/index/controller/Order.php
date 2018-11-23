<?php

namespace app\index\controller;

use think\Request;
class Order extends Common
{
    protected $beforeActionList = [
        'first' => ['only'=>'myorderdetailwait,myorderdetailcomplete,myorderdetailalready,lowerorderdetailwait,lowerorderdetailalready,ordermanagelist'],
    ];

    protected function first()
    {
        $user = session('user');
        $tip = '';
        if($user['role'] <= 0)
        {
            // if ($user['status'] == 1)
            // {
            //     $tip = 2;
            // }else{
                $tip = 1;
            // }
        }
        $this->assign('tip',$tip);
    }

	//订单列表
	public function orderManageList()
	{
         $this->assign('spider',['title'=>'订单管理']);
		// $this->assign('title','订单管理');
		return $this->fetch('orderManageList');
	}

	//我的订单
    public function myOrders()
    {
    	//初始化订单表模型
    	$order=model('Orders');
    	$refund=model('Agentorderrefund');
    	$return=model('Agentorderreturngoods');
    	$product=model('Products');

    	$user=session('user');
    	//数据获取
    	$agent_id=session('user.a_id');
    	$role=session('user.role');

    	$request=request();
    	$status=$request->param('status');

    	$data=array();
    	$data['agent_id']=$agent_id;
    	//$status==0 && $data['order_status']=['in',array(2,3,4)];
    	//if($user['generation']==1) {
    	$status==0 && $data['order_status']=['in',array(1,2,3,4,5,7)];
    	//}
    	$status>0 && $data['order_status']=$status;

    	//全部订单-订单表
    	$orderList=$order->where($data)->group('order_number')->order('id desc')->select();
    	foreach($orderList as $k=>$v)
    	{
    		$v['productList']=$order->where(array('order_number'=>$v['order_number']))->field('pname,pnumber,pprice,pid,ptotal_price')->select();
    		
    		$v['productInfo']=$product->field('is_agent_level')->find($v['pid']);
    		//校验下是否有退款
    		$v['order_status']==7 && $v['refundInfo']=$refund->where(array('order_number'=>$v['order_number']))->find();
    		
    		//校验下是否有退货
    		//设置已取消的订单不显示
    		$v['returnInfo']=$return->where(array('order_number'=>$v['order_number']))->find();
    		if($v['order_status']==7 &&empty($v['refundInfo']) && empty($v['returnInfo'])) {
    			unset($orderList[$k]);
    		}
    		
    		$ptotal_price=$agent_total_price=0;
    		foreach($v['productList'] as $kk=>$vv) {
    			$ptotal_price+=$vv['ptotal_price'];
    		}
    		//代理折扣价格  
    		$v['agent_total_price'] =$ptotal_price+$v['trans_expenses']-$v['order_amount_pay'];
    		$v['ptotal_price']=$ptotal_price;
    	}

    	$this->assign('orderList',$orderList);

 		$this->assign('status',$status);
 		$this->assign('user',$user);
 
        $this->assign('spider',['title'=>'我的订单']);
    	return $this->fetch('myOrders');
    }

    //获取订单详情
    public function orderDetail($id,$type=1)
    {
    	$order=model('Orders');
    	$promotionOrder=model('Promotiongiftorder');
    	$refund=model('Agentorderrefund');
    	$return=model('Agentorderreturngoods');
    	$setting=model('Agentordersetting');
    	$product=model('Products');

    	$user=session('user');
    	
    	//申请退货
    	$request=request();
    	$returnGoods=0;
		$request->param('returnGoods')>0 && $returnGoods=$request->param('returnGoods');
    	$this->assign('returnGoods',$returnGoods);

    	//获取退货的相关地址
    	$settingInfo=$setting->find();
    	$this->assign('settingInfo',$settingInfo);


    	//获取物流公司信息
    	// $delivery=model('Delivery');
    	// $expressComList=$delivery->expressName;
    	$data=array();
    	// $i=0;
        $expressComList = model('admin/Expresscompany')->getAllExpressCompany('',true);
    	foreach($expressComList as $k=>$v){
    		// $data[$i]['id']=$k;
    		// $data[$i]['value']=$v;
    		// $i++;
            $data_cache_express = [];
            $data_cache_express['id'] = $v['code'];
            $data_cache_express['value'] = $v['name'];
            $data[] = $data_cache_express;
    	}
    	$this->assign('expressComList',json_encode($data));

    	if($type==1) {
	    	//获取单一订单信息
	    	$orderDetail=$order->where("order_number='".$id."'")->find();
	    	
	    	//获取单一商品信息
	    	$productInfo=$product->find($orderDetail['pid']);
	    	$this->assign('productInfo',$productInfo);

	    	//获取商品列表的订单信息
	    	$productList=$order->where("order_number='".$id."'")->select();
	    	$this->assign('productList',$productList);

    	}

    	//礼包订单
    	if($type==2) {
    		$orderDetail=$promotionOrder->where("order_number='".$id."'")->find();
    	}

    	$orderDetail['ctime']=$orderDetail->getData('create_time');

    	$ptotal_price=$agent_total_price=0;
    	foreach($productList as $k=>$v) {
    		$ptotal_price+=$v['ptotal_price'];
    	}
    	//获取代理的折扣价 
    	$orderDetail['agent_total_price']=$ptotal_price+$orderDetail['trans_expenses']-$orderDetail['order_amount_pay'];
    	$orderDetail['ptotal_price']= $ptotal_price;

    	//获取订单的退款信息
    	$orderDetail['refundInfo']=$refund->where("order_number='".$id."'")->find();

    	//获取订单的退货信息
    	$orderDetail['returnInfo']=$return->where("order_number='".$id."'")->find();

    	$this->assign('orderDetail',$orderDetail);
    	$this->assign('type',$type);

    	$this->assign('spider',['title'=>'订单详情']);

    	$templateName='myOrderDetailWait';

    	if($type==1) {
    		//不同的订单状态加载不同的模板
    		$orderDetail['order_status']==3 && $templateName='myOrderDetailAlready';
    		$orderDetail['order_status']==4 && $templateName='myOrderDetailComplete';
    	}

    	if($type==2) {
    		//不同的订单状态加载不同的模板
    		$orderDetail['status']==3 && $templateName='myOrderDetailAlready';
    		$orderDetail['status']==4 && $templateName='myOrderDetailComplete';
    	}

    	return $this->fetch($templateName);
    }


    //下级订单列表
    public function lowerOrders()
    {
    	//初始化模型
    	$order=model('Orders');
    	$agent=model('Agents');
    	$promotionOrder=model('Promotiongiftorder');
    	$setting=model('Agentordersetting');
    	$refund=model('Agentorderrefund');
    	$consignee=model('Consignee');
    	$return=model('Agentorderreturngoods');

    	$request=request();
    	$status=$request->param('status');

    	$agent_id=session('user.a_id');
    	$role=session('user.role');
    	//获取下级的ID
    	$sonIdAry=$agent->getAgentDirectLowerList($agent_id);
 
    	$data=array();
    	$data['agent_id']=['in',$sonIdAry];
    	empty($status) && $status=0;
    	$status==0 && $data['order_status']=['in',array(2,3,4)];
    	$status>0 && $data['order_status']=$status;

    	//查询下级的订单列表
    	$orderList=$order->where($data)->group('order_number')->order('id desc')->select();
 
    	foreach($orderList as $k=>$v)
    	{
    		$v['consigneeInfo']=$consignee->getConsigneeInfoByOrderNumber($v['order_number']);
    		$v['productList']=$order->where(array('order_number'=>$v['order_number']))->field('pname,pnumber,pprice,pid,ptotal_price')->select();

    		//获取下单人的信息
    		$agentInfo=$agent->find($v['agent_id']);
    		
    		$ptotal_price=$agent_total_price=0;
    		foreach($v['productList'] as $kk=>$vv) {
    			$ptotal_price+=$vv['ptotal_price'];
    		}
    		//代理折扣价格
    		$v['agent_total_price'] =$ptotal_price+$v['trans_expenses']-$v['order_amount_pay'];
    		$v['ptotal_price']=$ptotal_price;
    		
    	}
    	$this->assign('orderList',$orderList);


    	$this->assign('status',$status);
        $this->assign('spider',['title'=>'下级订单']);

    	return $this->fetch('lowerOrders');
    }


    //下级订单详情
    public function lowerOrderDetail($id,$type=1)
    {
    	$order=model('Orders');
    	$promotionOrder=model('Promotiongiftorder');

    	$setting=model('Agentordersetting');
    	$refund=model('Agentorderrefund');
    	$agent=model('Agents');
    	
    	$user=session('user');

    	if($type==1) {
	    	$orderDetail=$order->where("order_number='".$id."'")->find();
	    	$orderDetail['deliveryWay']=$setting->getDeliveryWayByOrderNumber($id);
	    	$this->assign('orderDetail',$orderDetail);

	    	//获取下单人的信息
	    	$agentInfo=$agent->find($orderDetail['agent_id']);

	    	//获取商品列表的订单信息
	    	$productList=$order->where("order_number='".$id."'")->select();
	    	$this->assign('productList',$productList);
    	}

    	//大礼包订单
    	if($type==2) {
    		$orderDetail=$promotionOrder->where("order_number='".$id."'")->find();
    		$this->assign('orderDetail',$orderDetail);
    	}

    	//获取订单的退款信息
    	$orderDetail['refundInfo']=$refund->where("order_number='".$id."'")->find();
    	$orderDetail['ctime']=$orderDetail->getData('create_time');
    	
    	
    	$ptotal_price=$agent_total_price=0;
    	foreach($productList as $k=>$v) {
    		$ptotal_price+=$v['ptotal_price'];
    	}
    	//获取代理的折扣价
    	$orderDetail['agent_total_price']= $ptotal_price+$orderDetail['trans_expenses']-$orderDetail['order_amount_pay'];
    	$orderDetail['ptotal_price']= $ptotal_price;

    	$templateName='lowerOrderDetailWait';

    	//不同的订单状态加载不同的模板
    	if(isset($orderDetail['order_status'])) {
    		$orderDetail['order_status']==3 && $templateName='lowerOrderDetailAlready';
    	}
    	$type==2 && $templateName='lowerOrderDetailAlready';

    	//获取物流公司信息
    	$delivery=model('Delivery');
    	$expressComList=$delivery->expressName;
    	$data=array();
    	$i=0;
    	foreach($expressComList as $k=>$v){
    		$data[$i]['id']=$k;
    		$data[$i]['value']=$v;
    		$i++;
    	}
    	$this->assign('expressComList',json_encode($data));

    	$this->assign('type',$type);
    	$this->assign('spider',['title'=>'订单详情']);
    	return $this->fetch($templateName);
    }

    //更改订单状态
    public function changeOrderStatus()
    {
    	$res=0;

    	$request=request();

    	$user=session('user');

    	//获取参数
    	$order_number=$request->param('order_number');
    	$order_status=$request->param('order_status');
    	$type=$request->param('type');

    	//准备执行-订单
    	if($order_number && $order_status && $type==1)
    	{
    		$order=model('Orders');

    		$orderInfo=$order->where("order_number='".$order_number."'")->find();


    		$agent=model('Agents');
    		$agentInfo=$agent->find($user['a_id']);//获取代理商信息


    		$data=array();
    		$data['order_status']=$order_status;

    		//校验下自己的库存额度是否够用，如果可以那么设置公司发货，如果不行，那么提示
    		if($order_status==2){
    			if($agentInfo['stock_money']-$orderInfo['order_amount_pay']>0){
    				$data['delivery_agent_id']=1;//设置公司发货
	    		} else {
	    			return 2;//库存不足，公司发货失败
	    			exit;
	    		}
    		}


    		//如果是，取消订单，那么会自动插入到申请退款流程
    		if($order_status==7 && $orderInfo['order_status']==2){

    			//校验下 订单状态，如果支付了，那么自动插入申请退款，如果没有直接取消订单
    			$orderRefund=model('Agentorderrefund');

	    		$ndata=array();
	    		$ndata['order_number']=$orderInfo['order_number'];
	    		$ndata['create_time']=time();
	    		$ndata['order_amount_pay']=$orderInfo['order_amount_pay'];
	    		$ndata['refund_fee']=$orderInfo['order_amount_pay'];
	    		$ndata['agent_id']=$orderInfo['agent_id'];
	    		if($orderInfo['paystyle']==2) {
	    			$ndata['refund_pay_type']=1;
	    		} else if($orderInfo['paystyle']==1) {
	    			$ndata['refund_pay_type']=2;
	    		} else if($orderInfo['paystyle']==3) {
	    			$ndata['refund_pay_type']=3;
	    		}

	    		$ndata['type']=1;
	    		$orderRefund->save($ndata);

    		}



    		//如果订单状态设置为4 说明订单完成，那么发放订单相关的奖励
    		if($order_status==4) {
    		    $data['commplete_time']=time();
    			$res=provide_order_reward($order_number);
    		}

    		$res=$order->save($data,array('order_number'=>$order_number));

    		//如果设置订单取消，那么商品的销量回归，库存增加
    		if($order_status==7){
    			cancel_order_sales_and_stock($order_number,1);
    		}


    	}

    	//准备执行-大礼包订单
    	if($order_number && $order_status && $type==2)
    	{
    		$order=model('Promotiongiftorder');
    		$data=array();
    		$data['status']=$order_status;

    		$orderInfo=$order->where("order_number='".$order_number."'")->find();

    		//如果是在线支付，取消订单，那么会自动插入到申请退款流程
    		if($order_status==7 && $orderInfo['status']==2){

    			$orderRefund=model('Agentorderrefund');

    			$ndata=array();
    			$ndata['order_number']=$orderInfo['order_number'];
    			$ndata['create_time']=time();
    			$ndata['order_amount_pay']=$orderInfo['order_price'];
    			$ndata['refund_fee']=$orderInfo['order_price'];
    			$ndata['agent_id']=$orderInfo['agent_id'];

    			if($orderInfo['paystyle']==2) {
    				$ndata['refund_pay_type']=1;
    			} else if($orderInfo['paystyle']==1) {
    				$ndata['refund_pay_type']=2;
    			} else if($orderInfo['paystyle']==3) {
    				$ndata['refund_pay_type']=3;
    			}

    			$ndata['type']=2;
    			$orderRefund->save($ndata);
    		}


    		//如果订单状态设置为4 说明订单完成，那么发放大礼包订单相关的奖励
    		if($order_status==4) {
    			$data['complete_time']=time();
    			$res=provide_promotion_gift_order_reward_profit($order_number);
    		}
    		$res=$order->save($data,array('order_number'=>$order_number));

    		//如果设置订单取消，那么商品的销量回归，库存增加
    		if($order_status==7){
    			cancel_order_sales_and_stock($order_number,2);
    		}
    	}

    	return $res;
    }

    //下级订单发货
    public function lowerOrderDeliverySave()
    {
    	$res=0;

    	$request=request();

    	$user = session('user');

    	//数据获取
    	$data['order_number']=$request->param('order_number');
    	$data['type']=$request->param('type');
    	$data['express_code']=$request->param('express_code');

    	$delivery=model('Delivery');
    	$expressComList=$delivery->expressName;
    	$data['express_name']=$expressComList[$data['express_code']];

    	$data['express_number']=$request->param('express_number');
    	$data['remark']=$request->param('remark');
    	$data['create_time']=time();

    	//为空校验
    	if(!empty($data['order_number']) && !empty($data['express_name'])&& !empty($data['express_code']) && !empty($data['express_number']))
    	{
    		$delivery=model('Delivery');
    		$res=$delivery->save($data);

    		//修改订单表的状态
    		$order=model('Orders');
    		$ndata=array();
    		$ndata['order_status']=3;
    		$ndata['delivery_time']=time();
    		$ndata['delivery_agent_id']=$user['a_id'];
    		$ndata['delivery_id']=$delivery->id;
    		$order->save($ndata,array('order_number'=>$data['order_number']));
			
    		//给下级发货，增加库存
    		$stock=model('Agentstockchange');
    		$agent=model('Agents');
    		
    		$agentInfo=$agent->find($user['a_id']);
    		$orderInfo=$order->where("order_number='".$data['order_number']."'")->find();
    		
			$data=array();
			$data['agent_id']=$user['a_id'];
			$data['create_time']=time();
			$data['change_before']=$agentInfo['stock_money'];
			$data['money']=$orderInfo['order_amount_pay'];
			$data['change_after']=round($data['change_before']+$data['money'],2);
			$data['change_type']=11;//给下级发货加库存 
			$data['account_type']=5;//其他
			
			$data['audit_time']=time();
			$data['status']=2;//已审核
			$data['remark']='给下级发货加库存';
			$stock->isUpdate(false)->data($data, true)->save();
			
			//下级加库存
			$res=$agent->where('agent_id',$user['a_id'])->setInc('stock_money',$orderInfo['order_amount_pay']);
    		 
    	}

    	return $res;
    }

    //活动订单
    public function promotionGiftOrders()
    {

    	//初始化大礼包模型
    	$promotionOrder=model('Promotiongiftorder');

    	$orderRefund=model('Agentorderrefund');
    	$return=model('Agentorderreturngoods');

    	//数据获取
    	$agent_id=session('user.a_id');

    	$request=request();
    	$status=$request->param('status');

    	$data=array();
    	$data['agent_id']=$agent_id;
    	$status==0 && $data['status']=['in',array(1,2,3,4,5,7)];
    	$status>0 && $data['status']=$status;

    	//全部订单-大礼包
    	$promotionOrderList=$promotionOrder->where($data)->order('id','desc')->select();
    	//校验下是否有退款
    	foreach($promotionOrderList as $k=>$v) {
    		$v['status']==7 && $v['refundInfo']=$orderRefund->where("order_number='".$v['order_number']."'")->find();


    		if($v['status']==7 &&(empty($v['refundInfo']))) {
    			unset($promotionOrderList[$k]);
    		}
    	}
    	$this->assign('promotionOrderList',$promotionOrderList);


    	$this->assign('status',$status);
    	$this->assign('title','我的活动订单');

    	return $this->fetch('promotionGiftOrders');
    }

    //物流信息获取
    public function expressInfo()
    {
    	$request=request();

    	$order_number=$request->param('order_number');
    	$type=$request->param('type');//1商品订单2礼包订单

    	$order=model('Orders');
    	$promotionOrder=model('Promotiongiftorder');

    	$orderDetail=$productList=array();

    	if($type==1) {
    		//获取单一订单信息
    		$orderDetail=$order->where("order_number='".$order_number."'")->find();

    		//获取商品列表的订单信息
    		$productList=$order->where("order_number='".$order_number."'")->select();

    	}


    	if($type==2) {
    		$orderDetail=$promotionOrder->where("order_number='".$order_number."'")->find();
    	}

    	//赋值
    	$this->assign('type',$type);
    	$this->assign('orderDetail',$orderDetail);
    	$this->assign('productList',$productList);


    	//物流信息查询
		$expressInfoUrl=0;
		$expressInfoUrl=get_express_info($order_number,$type);
		$this->assign('expressInfoUrl',$expressInfoUrl);

    	$this->assign('spider',['title'=>'物流查询']);

    	return $this->fetch('expressInfo');
    }

    //校验下订单是否改过价格
    public function checkOrderPriceChange()
    {
    	$request=request();
    	$order_number=$request->param('order_number');
    	$order_amount_pay=$request->param('order_amount_pay');
    	$order_type = $request->param('order_type');// CYL 类型(用于区分充值与订单支付)

    	$res=0;
     	if($order_number && $order_amount_pay && $order_type!=2) {
            if($order_type == 3)
            {// CYL 充值
                $res = model('Agentstockchargeorder')->checkOrderPriceIsChange($order_number,$order_amount_pay);
            }else{
                $order=model('Orders');
                $res=$order->checkOrderNumberPriceChange($order_number,$order_amount_pay);
            }
     	}
    	return json_encode($res);
    }

    //申请订单退货
    public function applyOrderReturnGoods()
    {
    	$request=request();
    	$order_number=$request->param('order_number');

    	$data=array();
    	//数据获取
    	$data['order_number']=$request->param('order_number');
    	$data['express_code']=$request->param('express_code');

        //根据物流公司的代码，查找物流公司的名称
	    $company=model('admin/Expresscompany');
	    if($data['express_code']) {
	    	$data['express_name']=$company->where("code='".$data['express_code']."'")->value('name');
	    }

    	$data['express_number']=$request->param('express_number');
    	$data['remark']=$request->param('remark');
    	$data['create_time']=time();
    	$data['type']=$request->param('type');

    	$user=session('user');
    	$data['agent_id']=$user['a_id'];

    	$res=0;

    	if($order_number) {
    		//校验订单号是否存在
    		$order=model('Orders');
    		$orderInfo=$order->where("order_number='".$data['order_number']."'")->find();
    		if(!empty($orderInfo)) {
    			$returnGoods=model('Agentorderreturngoods');
    			$res=$returnGoods->save($data);
    		}

    	}

    	return json_encode($res);
    }
}
