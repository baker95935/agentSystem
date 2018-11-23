<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Validate;
use think\Session;

class Agentorders extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$request = request();

    	$status=$agent_id=$order_number=$create_time=$end_time=$delivery_agent_id=$pname=$consignee_name=$consignee_phone=$address=$province=$city=0;
    	$area=$express_number=$express_name=$order_type=0;

    	//订单表数据
    	$request->param('order_status') && $status=$request->param('order_status');
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	$request->param('create_time') && $create_time=$request->param('create_time');
    	$request->param('end_time') && $end_time=$request->param('end_time');
    	$request->param('delivery_agent_id') && $delivery_agent_id=$request->param('delivery_agent_id');
    	$request->param('pname') && $pname=$request->param('pname');


    	//收货人表数据
    	$request->param('consignee_name') && $consignee_name=$request->param('consignee_name');
    	$request->param('consignee_phone') && $consignee_phone=$request->param('consignee_phone');
    	$request->param('address') && $address=$request->param('address');
    	$request->param('province') && $province=$request->param('province');
    	$request->param('city') && $city=$request->param('city');
    	$request->param('area') && $area=$request->param('area');

    	//发货表数据
    	$request->param('express_number') && $express_number=$request->param('express_number');
    	$request->param('express_name') && $express_name=$request->param('express_name');

    	//发货订单类型
    	$request->param('order_type') && $order_type=$request->param('order_type');

    	$orders=model('Agentorders');

    	//订单表状态
    	$data=array();
    	$status>0 && $data['order_status']=$status;

    	//发货后，订单状态才起作用
    	if($status!=2){
    		$order_type==2 && $data['delivery_agent_id']=['>',1];
    		$order_type==1 && $data['delivery_agent_id']=1;
    	}


    	!empty($agent_id) && $data['Agentorders.agent_id']=$agent_id;
    	!empty($order_number) && $data['Agentorders.order_number']=$order_number;


    	$create_time>0 && $data['Agentorders.create_time']=['>=',strtotime($create_time)];
    	if($end_time>0){
    		$tmp_time=strtotime($end_time)+60*60*24;
    		$data['Agentorders.create_time']=['<=',$tmp_time];
    	}

    	if(!empty($create_time) && !empty($end_time)) {
    		$tmp_time=strtotime($end_time)+60*60*24;
    		$data['Agentorders.create_time']=['between time',[$create_time,$tmp_time]];
    	}


    	!empty($delivery_agent_id) && $data['delivery_agent_id']=$delivery_agent_id;
    	!empty($pname) && $data['pname']=['like','%'.$pname.'%'];



    	//收货人表的查询
    	$cdata=array();
    	!empty($consignee_name) && $cdata['consignee_name']=$consignee_name;
    	!empty($consignee_phone) && $cdata['consignee_phone']=$consignee_phone;
    	!empty($address) && $cdata['address']=$address;
    	!empty($province) && $cdata['province']=$province;
    	!empty($city) && $cdata['city']=$city;
    	!empty($area) && $cdata['area']=$area;

    	//groupbyTP5分页有小BUG 先计算出总算 然后传给分页
    	$totalNumber=$orderList=$orders->hasWhere('address',$cdata)
	    	->where($data)
	    	->order('id', 'desc')
	    	->group('order_number','desc')->count();


    	$orderList=$orders->hasWhere('address',$cdata)
	    	->where($data)
	    	->order('id', 'desc')
	    	->group('order_number','desc')
	    	->paginate(config('paginate.list_rows'),$totalNumber,['query'=>$request->param()]);

    	$setting=model('Agentordersetting');
    	//循环获取商品中的订单信息
    	foreach($orderList as $k=>&$v)
    	{
    		$v['productList']=$orders->where(array('order_number'=>$v['order_number']))->field('pname,pnumber,pprice,pid,ptotal_price')->select();
    		//如果是已取消的订单并且在线支付，查看退款状态

    		if($v->getData('order_status')==7 && $v->getData('paystyle')==2) {
    			$refund=model('Agentorderrefund');
    			$tmp=$refund->getAuthStatusByOrderNumber($v['order_number']);
    			if($tmp==1) {
    				$v['refund_status']='未退款';
    			} elseif($tmp==2){
    				$v['refund_status']='退款成功';
    			} else {
    				$v['refund_status']='退款失败';
    			}
    		}

    		//是否有发货按钮
    		$v['canDelivery']=0;
    		$v->getData('paystyle')==3 && $v['canDelivery']=1;
    		$v->getData('paystyle')==1 && $v['canDelivery']=1;
    		//增加校验，支付状态
    		if($v->getData('paystyle')==2 && isset($v['pay_time'])) {
    			$v['canDelivery']=1;
    		}
    		$v['deliveryWay']=$setting->getDeliveryWayByOrderNumber($v['order_number']);

    	}

    	$this->assign('orderList',$orderList);

    	$orderStatus=$orders->orderStatus;
    	$this->assign('orderStatus',$orderStatus);

    	//获取省份信息
    	$dataAddress=model('BasicDataAddress');
    	$provinces = $dataAddress->getProviceList();
    	$this->assign('provinces',$provinces);

    	//页面赋值
    	$this->assign('agent_id',$agent_id);
    	$this->assign('order_number',$order_number);
    	$this->assign('create_time',$create_time);
    	$this->assign('end_time',$end_time);
    	$this->assign('delivery_agent_id',$delivery_agent_id);
    	$this->assign('pname',$pname);

    	$this->assign('consignee_name',$consignee_name);
    	$this->assign('consignee_phone',$consignee_phone);
    	$this->assign('address',$address);
    	$this->assign('province',$province);
    	$this->assign('city',$city);
    	$this->assign('area',$area);

    	$this->assign('order_type',$order_type);
    	$this->assign('order_status',$status);

    	//获取下默认状态下的 市和县的名称
    	$city_info=$area_info=0;
    	!empty($city) && $city_info=$dataAddress->find($city);
    	!empty($area) && $area_info=$dataAddress->find($area);
    	$this->assign('city_info',$city_info);
    	$this->assign('area_info',$area_info);

    	$this->assign('express_number',$express_number);
    	$this->assign('express_name',$express_name);

    	//获取发货公司的列表
    	$delivery=model('Agentorderdelivery');
    	$expressComList=model('Expresscompany')->getAllExpressCompany('',true);
    	$this->assign('expressComList',$expressComList);

    	return $this->fetch();
    }

    //导出
    public function excelIndex()
    {
    	$request = request();

    	$status=$agent_id=$order_number=$create_time=$end_time=$delivery_agent_id=$pname=$consignee_name=$consignee_phone=$address=$province=$city=0;
    	$area=$express_number=$express_name=$order_type=0;

    	//订单表数据
    	$request->param('order_status') && $status=$request->param('order_status');
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	$request->param('create_time') && $create_time=$request->param('create_time');
    	$request->param('end_time') && $end_time=$request->param('end_time');
    	$request->param('delivery_agent_id') && $delivery_agent_id=$request->param('delivery_agent_id');
    	$request->param('pname') && $pname=$request->param('pname');


    	//收货人表数据
    	$request->param('consignee_name') && $consignee_name=$request->param('consignee_name');
    	$request->param('consignee_phone') && $consignee_phone=$request->param('consignee_phone');
    	$request->param('address') && $address=$request->param('address');
    	$request->param('province') && $province=$request->param('province');
    	$request->param('city') && $city=$request->param('city');
    	$request->param('area') && $area=$request->param('area');


    	$request->param('order_type') && $order_type=$request->param('order_type');

    	$orders=model('Agentorders');

    	//订单表状态
    	$data=array();
    	$status>0 && $data['order_status']=$status;

    	//发货后，订单状态才起作用
    	if($status!=2){
    		$order_type==2 && $data['delivery_agent_id']=['>',1];
    		$order_type==1 && $data['delivery_agent_id']=1;
    	}

    	!empty($agent_id) && $data['Agentorders.agent_id']=$agent_id;
    	!empty($order_number) && $data['Agentorders.order_number']=$order_number;


    	$create_time>0 && $data['Agentorders.create_time']=['>=',strtotime($create_time)];
    	if($end_time>0){
    		$tmp_time=strtotime($end_time)+60*60*24;
    		$data['Agentorders.create_time']=['<=',$tmp_time];
    	}

    	if(!empty($create_time) && !empty($end_time)) {
    		$tmp_time=strtotime($end_time)+60*60*24;
    		$data['Agentorders.create_time']=['between time',[$create_time,$tmp_time]];
    	}


    	!empty($delivery_agent_id) && $data['delivery_agent_id']=$delivery_agent_id;
    	!empty($pname) && $data['pname']=['like','%'.$pname.'%'];

    	//发货人表的查询
    	$edata=array();
    	!empty($express_number) && $edata['agent_order_delivery.express_number']=$express_number;
    	!empty($express_name) && $edata['agent_order_delivery.express_name']=$express_name;

    	//收货人表的查询
    	$cdata=array();
    	!empty($consignee_name) && $cdata['consignee_name']=$consignee_name;
    	!empty($consignee_phone) && $cdata['consignee_phone']=$consignee_phone;
    	!empty($address) && $cdata['address']=$address;
    	!empty($province) && $cdata['province']=$province;
    	!empty($city) && $cdata['city']=$city;
    	!empty($area) && $cdata['area']=$area;


    	$orderList=$orders->hasWhere('address',$cdata)
    		->where($data)
    		->order('id', 'desc')
    		->group('order_number','desc')
    		->select();


    	//开始导出相关
    	$title=array('订单编号','订单状态','商品名称','商品总价','数量','收货人','实付金额');
    	$filename='订单记录';

    	$data=array();
    	foreach($orderList as $k=>$v)
    	{
    		$data[$k]['order_number']="编号：".$v->order_number;
    		$data[$k]['status']=$v['order_status'];

    		$data[$k]['pname']=$v['pname'];
    		$data[$k]['ptotal_price_pay']=$v['ptotal_price_pay'];
    		$data[$k]['pnumber']=$v['pnumber'];

    		$data[$k]['consignee_name']='姓名：'.$v->address->consignee_name." 电话：".$v->address->consignee_phone;
    		$data[$k]['order_amount_pay']=$v['order_amount_pay'];

    	}

    	$this->exportexcel($data,$title,$filename);

    }


    /**
     * 显示订单详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read()
    {
   		$orders=model('Agentorders');
   		$consignee=model('Agentorderconsigneeaddress');
   		$delivery=model('Agentorderdelivery');
   		$reward=model('Agentorderreward');
   		$setting=model('Agentordersetting');
   		$finan=model('AgentFinancialAccount');
   		$agent=model('Agents');

   		$request=request();
   		$id=$request->param('id/s');

   		//获取订单主表数据
   		$ordersInfo=$orders->where("order_number='".$id."'")->find();
   		//获取发货方式
   		$ordersInfo['deliveryWay']=$setting->getDeliveryWayByOrderNumber($id);

   		$agentInfo=$agent->find($ordersInfo['agent_id']);
   		$this->assign('agentInfo',$agentInfo);
   		
   		//是否有发货按钮
   		$ordersInfo['canDelivery']=0;
   		$ordersInfo->getData('paystyle')==3 && $ordersInfo['canDelivery']=1;
   		$ordersInfo->getData('paystyle')==1 && $ordersInfo['canDelivery']=1;
   		//增加校验，支付状态
   		if($ordersInfo->getData('paystyle')==2 && isset($ordersInfo['pay_time'])) {
   			$ordersInfo['canDelivery']=1;
   			$accountInfo=$finan->where("a_id='".$ordersInfo['agent_id']."' and type=3")->find();
   			$ordersInfo['wechatAccount']=$accountInfo['account'];
   		}

   		$this->assign('ordersInfo',$ordersInfo);


   		//获取订单收货表数据
   		$ordersConsignee=$consignee->where("order_number='".$id."'")->find();
   		$this->assign('ordersConsignee',$ordersConsignee);

   		//获取订单发货表数据
   		$ordersDelivery=$delivery->where("order_number='".$id."'")->find();
   		$this->assign('ordersDelivery',$ordersDelivery);

   		//获取订单对应的商品数据
   		$productList=$orders->field('pname,pprice,ptotal_price,pnumber,pid,id')->where("order_number='".$id."'")->select();
   		$this->assign('productList',$productList);

   		$ptotal=0;
   		$agent_order_id_ary=array();
   		//获取订单对应的商品总计
   		foreach($productList as $k=>$v){
   			$ptotal=$ptotal+$v['ptotal_price'];
   			$agent_order_id_ary[]=$v['id'];
   		}
   		$this->assign('ptotal',$ptotal);

   		$productRewardList=array();
   		foreach($productList as $k=>$v) {

   			$productRewardList[$k]['pname']=$v['pname'];
	   		//奖励表消息-招商奖励
	   		$productRewardList[$k]['ordersRewardRecommend']=$reward->getOrdersRewardInfo($id,1,$v['pid']);

	   		//奖励表消息-代理间接奖励
	   		$productRewardList[$k]['ordersRewardWhole']=$reward->getOrdersRewardInfo($id,3,$v['pid']);

	   		//奖励表消息-代理直接奖励
	   		$productRewardList[$k]['ordersRewardSelf']=$reward->getOrdersRewardInfo($id,4,$v['pid']);
   		}


   		//如果还没有奖励，那么计算计算
   		if($ordersInfo->getData('order_status')<=2) {

   			$wholeReward=agent_reward_whole_sale($id,2);
   			$recommendReward=agent_reward_recommend($id,2);
   			$selfReward=agent_reward_self_sale($id,2);

   			foreach($productList as $k=>$v) {
   				$productRewardList[$k]['pname']=$v['pname'];
   				//招商奖励
   				if(!empty($recommendReward)) {
	   				foreach($recommendReward as $kk=>$vv) {
	   					if($vv['product_id']==$v['pid']){
	   						$productRewardList[$k]['ordersRewardRecommend']['list'][$kk]=$vv;
	   						$productRewardList[$k]['ordersRewardRecommend']['count']=count($recommendReward);
	   					}
	   				}
   				}
   				//间接奖励
   				if(!empty($wholeReward)) {
	   				foreach($wholeReward as $kk=>$vv) {
	   					if($vv['product_id']==$v['pid']){
	   						$productRewardList[$k]['ordersRewardWhole']['list'][$kk]=$vv;
	   						$productRewardList[$k]['ordersRewardWhole']['count']=count($wholeReward);
	   					}
	   				}
   				}

   				//直接奖励
   				if(!empty($selfReward)) {
   					foreach($selfReward as $kk=>$vv) {
   						if($vv['product_id']==$v['pid']){
   							$productRewardList[$k]['ordersRewardSelf']['list'][$kk]=$vv;
   							$productRewardList[$k]['ordersRewardSelf']['count']=count($selfReward);
   						}
   					}
   				}

			}
   		}

   		$this->assign('productRewardList',$productRewardList);


   		//获取订单价格修改记录
   		$changePrice=model('admin/Agentorderchangeprice');
   		//获取订单表中的唯一ID
   		$agent_order_ids="";
   		!empty($agent_order_id_ary) && $agent_order_ids=implode(',',$agent_order_id_ary);

   		//获取数据
   		$changePriceCount=$changePrice->where("agent_order_ids='".$agent_order_ids."'")->count();
   		if($changePriceCount>0) {
   			$changePriceList=$changePrice->where("agent_order_ids='".$agent_order_ids."'")->select();
   			$this->assign('changePriceList',$changePriceList);
   		}
   		$this->assign('changePriceCount',$changePriceCount);


   		//获取发货公司的列表
   		$expressComList=model('Expresscompany')->getAllExpressCompany('',true);
   		$this->assign('expressComList',$expressComList);

   		return $this->fetch();
    }


    /**
     * 发货操作
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
    	//获取订单ID
    	$order_number=$request->param('id');

    	if($order_number) {
	        //然后更新下发货表的
	        $ndata=array();
	        $delivery=model('Agentorderdelivery');

	        $ndata['express_code']=$request->param('express_code');

    	    //根据物流公司的代码，查找物流公司的名称
		    $company=model('Expresscompany');
		    if($ndata['express_code']) {
		    	$ndata['express_name']=$company->where("code='".$ndata['express_code']."'")->value('name');
		    }


	        $ndata['express_number']=$request->param('express_number');
	        $ndata['remark']=$request->param('express_remark');
	        $ndata['id']=$request->param('express_id');

	        //数据校验
	        $validate = validate('Agentordersdelivery');

	        if(!$validate->check($ndata)){
	        	$this->error($validate->getError());
	        } else {

	        	$result=0;
	        	if(empty($ndata['id'])){//添加
	        		$ndata['create_time']=time();
	        		$ndata['order_number']=$order_number;
	        		$result=$delivery->save($ndata);
	        		$delivery_id=$delivery->id;
	        	} else {
	        		$result=$delivery->save($ndata,array('id'=>$ndata['id']));//更新
	        	}

	        }

	        //更新下状态，订单表的
	        $data=array();
	        $orders=model('Agentorders');

	        if($ndata['id']){
	        	$data['delivery_id']=$ndata['id'];
	        } else {
	        	$data['delivery_id']=$delivery_id;
	        }

	        $data['order_status']=3;
	        $data['delivery_time']=time();
	        $data['delivery_agent_id']=1;
	        $result=$orders->save($data,array('order_number'=>$order_number));

	        //发货了，订单计算奖励信息
	        $productInfo=$orders->where("order_number='".$order_number."'")->find();
	        
	        //改为非库存支付有奖励
			if(in_array($productInfo->getData('paystyle'),array(1,2))) {
			    
			    //代理间接销售收益
			    agent_reward_whole_sale($order_number);
			    //代理们的招商收益
			    agent_reward_recommend($order_number);
			    //代理直接奖励
			    agent_reward_self_sale($order_number);
	 
			}

	        //获取订单详情，然后发送消息
	        $orderInfo=$orders->where("order_number='".$order_number."'")->find();
	        $mdata['first']='您的订单已发货！';
	        $mdata['orderProductPrice']=$orderInfo['order_amount_pay'];
	        $mdata['orderProductName']=$orderInfo['pname'];
	        $mdata['orderName']=$orderInfo['order_number'];
	        $mdata['url']=config('domain').'index/Order/myOrders/';
	        $mdata['remark']='物流方式:'.$ndata['express_name'].'，物流编号:'.$ndata['express_number'];
	        !empty($ndata['remark']) && $mdata['remark']='物流方式:'.$ndata['express_name'].'，物流编号:'.$ndata['express_number'].'，发货备注：'.$ndata['remark'];


          	// 店铺统计下单数和销售额
          	$pids_arr = $orders->where("order_number='".$order_number."'")->column('pid');
	        auto_accumulation_order_and_money($orderInfo['agent_id'],$pids_arr,$orderInfo['order_amount']);

	        //给下单人发送发货通知
	        message_notification(2,$orderInfo['agent_id'],$mdata);
	        $this->success('操作成功', '/admin/Agentorders/index/');

    	}
    }
    /**
	 * 标记发货操作
	 */
	public function toDeliver(Request $request){

		$order_number=$request->param('id');
		//然后更新下发货表的
		$ndata=array();
		$delivery=model('Agentorderdelivery');

	    $ndata['express_code']=$request->param('express_code');

	    //根据物流公司的代码，查找物流公司的名称
	    $company=model('Expresscompany');
	    if($ndata['express_code']) {
	    	$ndata['express_name']=$company->where("code='".$ndata['express_code']."'")->value('name');
	    }

	    $ndata['express_number']=$request->param('express_number');

		$ndata['remark']=$request->param('express_remark');
		$ndata['agent_id']=0;
		$ndata['create_time']=time();
		$ndata['order_number']=$order_number;

		$result=0;

		$result=$delivery->save($ndata);
		$delivery_id=$delivery->id;


		//更新下状态，订单表的
		$data=array();
		$orders=model('Agentorders');
		$data['delivery_id']=$delivery_id;
		$data['order_status']=3;
		$data['delivery_time']=time();
		$data['delivery_agent_id']=1;
		$result=$orders->save($data,array('order_number'=>$order_number));

		//发货了，订单计算奖励信息
		$productInfo=$orders->where("order_number='".$order_number."'")->find();
	 
       //改为非库存支付有奖励
		if(in_array($productInfo->getData('paystyle'),array(1,2))) {
		    
		    //代理间接销售收益
		    agent_reward_whole_sale($order_number);
		    //代理们的招商收益
		    agent_reward_recommend($order_number);
		    //代理直接奖励
		    agent_reward_self_sale($order_number);
 
		}

		//获取订单详情，然后发送消息
		$orderInfo=$orders->where("order_number='".$order_number."'")->find();
		$mdata['first']='您的订单已发货！';
		$mdata['orderProductPrice']=$orderInfo['order_amount_pay'];
		$mdata['orderProductName']=$orderInfo['pname'];
		$mdata['orderName']=$orderInfo['order_number'];
		$mdata['url']=config('domain').'index/Order/myOrders/';
		$mdata['remark']='物流方式:'.$ndata['express_name'].'，物流编号:'.$ndata['express_number'];
		!empty($ndata['remark']) && $mdata['remark']='物流方式:'.$ndata['express_name'].'，物流编号:'.$ndata['express_number'].'，发货备注：'.$ndata['remark'];

		//给下单人发送发货通知
		message_notification(2,$orderInfo['agent_id'],$mdata);

		if($result==0){
			return json_encode(['code'=>'-1','msg'=>'发货失败']);
		}else{
      		//店铺统计下单数和销售额
      		$pids_arr = $orders->where("order_number='".$order_number."'")->column('pid');
      		auto_accumulation_order_and_money($orderInfo['agent_id'],$pids_arr,$orderInfo['order_amount']);
			return json_encode(['code'=>'0','msg'=>'发货成功']);
		}
	}


    //保存订单备注
    public function saveOrderMark()
    {
    	$request = request();

    	//数据获取
    	$order_number=$request->param('order_number');
    	$mark=$request->param('order_remark');

    	$data=array();
    	$data['remark']=$mark;

    	$orders=model('Agentorders');
    	$orders->save($data,array('order_number'=>$order_number));

    	return  1;
    }


    //修改订单价格
    public function changeOrderTotalPrice(Request $request)
    {
    	$orders=model('Agentorders');
    	$id=$request->param('id');
    	//获取订单数据
    	$orderInfo=$orders->where(array('order_number'=>$id))->select();
    	$this->assign('orderInfo',$orderInfo);

    	$this->assign('order_number',$id);

    	$order_amount=$order_amount_pay=0;
    	//获取订单对应的商品总计
    	foreach($orderInfo as $k=>$v){
    		$order_amount=$v['order_amount'];
    		$order_amount_pay=$v['order_amount_pay'];
    	}
    	$this->assign('order_amount',$order_amount);
    	$this->assign('order_amount_pay',$order_amount_pay);

    	return $this->fetch('changeOrderTotalPrice');
    }


    public function getOrderTotalPrice(Request $request){
		$orders=model('Agentorders');
    	$id=$request->param('id');
    	//获取订单数据
    	$orderInfo=$orders->where(array('order_number'=>$id))->select();
    	$data['orderInfo']=$orderInfo;

    	$order_amount=$order_amount_pay=0;
    	//获取订单对应的商品总计
    	foreach($orderInfo as $k=>$v){
    		$order_amount=$v['order_amount'];
    		$order_amount_pay=$v['order_amount_pay'];
		}
		$data['order_amount']=$order_amount;

    	$data['order_amount_pay']=$order_amount_pay;

    	return json_encode($data);
	}


    //保存修改订单价格
    public function changeOrderTotalPriceSave()
    {
    	$orders=model('Agentorders');
    	$request=request();
    	$result=0;
    	//获取订单信息
		$order_number=$request->param('order_number');
		$orderStr=$request->param('order_data');
		$newPay=$request->param('newPay');

		$strAry = array_filter(explode(",",$orderStr));
		$orderAmountPay=0;
		foreach ($strAry as $key => $value) {

			$strdata=array_filter(explode("|",$value));
			$orderAmountPay+=floatval($strdata[1]);
			$result=$orders->where('id',intval($strdata[0]))
						   ->where('order_number',$order_number)
						   ->update(['ptotal_price_pay'=>floatval($strdata[1])]);

		}

		//记录订单修改，并且生成新的订单号
		$new_order_number=0;
		$new_order_number=agent_order_change_price($order_number,$orderAmountPay,Session::get('username'));

		//价格修改
		$result=$orders->where('order_number',$order_number)->update(['order_amount_pay'=>floatval($orderAmountPay)]);

		//订单标号修改
		if($new_order_number>0) {
			$result=$orders->where('order_number',$order_number)->update(['order_number'=>$new_order_number]);
		}

    	return json_encode($result);
    }


    //修改收货人信息
    public function changeConsigneeInfo(Request $request)
    {

		//获取省份信息
		$provinces = model('BasicDataAddress')->getProviceList();

    	$this->assign('provinces',$provinces);

    	//获取收货人信息
		$consignee=model('Agentorderconsigneeaddress');
		$id=$request->param('id');
    	$consigneeInfo=$consignee->where(array('order_number'=>$id))->find();
    	$this->assign('consigneeInfo',$consigneeInfo);

    	//获取下默认状态下的 市和县的名称
    	$city_info=$area_info=0;
    	$dataAddress=model('BasicDataAddress');
    	!empty($consigneeInfo['city']) && $city_info=$dataAddress->find($consigneeInfo['city']);
    	!empty($consigneeInfo['area']) && $area_info=$dataAddress->find($consigneeInfo['area']);
    	$this->assign('city_info',$city_info);
    	$this->assign('area_info',$area_info);

    	return $this->fetch('changeConsigneeInfo');
    }


	//获取订单中的收货人信息
	public function initGetConsigneeaddress(Request $request){

		$consignee=model('Agentorderconsigneeaddress');
		$id=$request->param('id');
    	$consigneeInfo=$consignee->where(array('order_number'=>$id))->find();
		return json_encode($consigneeInfo);
	}


    //保存修改的收货人信息
    public function changeConsigneeInfoSave(Request $request)
    {


    	$data=array();
    	$data['consignee_name']=$request->param('consignee_name');
    	$data['consignee_phone']=$request->param('consignee_phone');
    	$data['province']=$request->param('province');
    	$data['city']=$request->param('city');
    	$data['area']=$request->param('area');
    	$data['address']=$request->param('address');

        $data['order_number']=$request->param('order_number');
    	$data['id']=$request->param('consignee_id');



		$consignee=model('Agentorderconsigneeaddress');
    	if(empty($data['id'])){//添加

			$data['create_time']=time();
			$result=$consignee->save($data);

		} else {

			$result=$consignee->save($data,array('id'=>$data['id']));//更新
		}

    	// $this->success('操作成功', '/admin/Agentorders/index/');
    	return json_encode($result);

    }

    //订单发货
    public function orderDelivery(Request $request)
    {
		$id=$request->param('id');
		$delivery=model('Agentorderdelivery');
    	$deliveryInfo=$delivery->where(array('order_number'=>$id))->find();
    	$this->assign('deliveryInfo',$deliveryInfo);
    	$this->assign('order_number',$id);

    	return $this->fetch('orderDelivery');
    }

    //打印发货单
	public function printDeliveryReview($id)
	{
		//订单数据发货人信息
		$delivery=model('Agentorderdelivery');
		$deliveryInfo=$delivery->where(array('order_number'=>$id))->find();
		$this->assign('deliveryInfo',$deliveryInfo);

		//订单数据收货人信息
		$address=model('Agentorderconsigneeaddress');
		$addressInfo=$address->where(array('order_number'=>$id))->find();
		$this->assign('addressInfo',$addressInfo);

		//订单中的商品列表
		$orders=model('Agentorders');
		$productList=$orders->where(array('order_number'=>$id))->select();
		$this->assign('productList',$productList);

		//订单信息
		$orderInfo=$orders->where(array('order_number'=>$id))->find();
		$this->assign('orderInfo',$orderInfo);

		return $this->fetch('printDeliveryReview');
	}

	//订单设置
	public function orderSetting()
	{
		//获取等级列表
		$level=model('Agentlevel');
		$listLevel=$level->order('id', 'asc')->select();
		$this->assign('listLevel',$listLevel);

		//加载模型
		$freight=model('Agentordersettingfreight');
		$setting=model('Agentordersetting');

		//获取订单设置的值
		$settingInfo=$setting->find();
		$this->assign('settingInfo',$settingInfo);

		//获取订单运费设置的值
		$freightList=$freight->order('id','asc')->select();
		$this->assign('freightList',$freightList);



		//获取每个等级对应的最低的额度作为默认值
		$agency=model('Agentrewardagency');
		foreach($listLevel as $k=>&$v)
		{
			$tmp=$agency->where(array('role'=>$v['id']))->find();
			$v['order_amount']=round($tmp['pre_deposit']*$tmp['lowest_limit']/100,2);
		}

		return $this->fetch('orderSetting');
	}

	//保存订单设置
	public function saveOrderSetting(Request $request)
	{
		$data=$ndata=array();

		$res=$result=0;

		// $freight=model('Agentordersettingfreight');
		$setting=model('Agentordersetting');

		//订单设置表
		$data['auto_confirm_time']=$request->param('auto_confirm_time');
		$data['time_span']=$request->param('time_span');
		$data['return_goods_address']=$request->param('return_goods_address');
		$tmp=$request->param('consignment_info/a');
		if(count($tmp)==2) {//如果是2个都选择，那么设置3，否则直接存
			$data['consignment_info']=3;
		} else {
			$data['consignment_info']=$tmp[0];
		}
		$data['id']=$request->param('settingInfoId');

		//订单设置运费表
		/*$level=model('Agentlevel');
		$listLevel=$level->order('id', 'asc')->select();
		foreach($listLevel as $k=>$v)
		{
			$ndata[$k]['role']=$v['id'];
			$ndata[$k]['order_amount']=$request->param('order_amount_'.$v['id']);
			$ndata[$k]['freight']=$request->param('freight_'.$v['id']);
			$ndata[$k]['create_time']=time();
			$ndata[$k]['id']=$request->param('id_'.$v['id']);;
		}*/

		//添加验证 订单设置表
		$validate = validate('Agentordersetting');

		if(!$validate->check($data)){
			$this->success($validate->getError());
		}

		//添加验证 订单设置运费表
		/*$validate = validate('Agentordersettingfreight');

		foreach($ndata as $k=>$v) {
			if(!$validate->check($v)){
				$this->success($validate->getError());
			}
		}*/

		//订单设置表添加
		if($data['id']) {
			$res=$setting->save($data,array('id'=>$data['id']));
		} else {
			$res=$setting->save($data);
		}

		//订单运费添加
		// $result=$freight->saveAll($ndata);

		$this->success('操作成功', '/admin/Agentorders/orderSetting/');

	}

	//物流信息跟踪显示
	public function expressInfo($order_number)
	{
		//订单数据发货人信息
		$delivery=model('Agentorderdelivery');
		$deliveryInfo=$delivery->where(array('order_number'=>$order_number))->find();
		$this->assign('deliveryInfo',$deliveryInfo);

		//订单数据收货人信息
		$address=model('Agentorderconsigneeaddress');
		$addressInfo=$address->where(array('order_number'=>$order_number))->find();
		$this->assign('addressInfo',$addressInfo);

		//订单中的商品列表
		$orders=model('Agentorders');
		$productList=$orders->where(array('order_number'=>$order_number))->select();
		$this->assign('productList',$productList);

		//订单信息
		$orderInfo=$orders->where(array('order_number'=>$order_number))->find();
		$this->assign('orderInfo',$orderInfo);

		//物流信息查询
		$expressInfoUrl=0;
		$expressInfoUrl=get_express_info($order_number,1);
		$this->assign('expressInfoUrl',$expressInfoUrl);

		return $this->fetch('expressInfo');
	}

	//获取快递公司列表数据
	public function getDeliveryCompanyInfo(Request $request){

		// $delivery=model('Agentorderdelivery');
		// $data=array();
		// $data=$delivery->expressName;
    $data = model('Expresscompany')->getAllExpressCompany('',true);
		return json_encode($data);
	}

	public function test()
	{
		$res=agent_reward_whole_sale('10008220180917141752',2);
		var_dump($res);
	}

}
