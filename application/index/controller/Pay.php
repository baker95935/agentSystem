<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;




class Pay extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function wechat()
    {
    	if(!session('?user'))
    	{
    		$this->redirect('Index/login');
    	}

		ini_set('date.timezone','Asia/Shanghai');
		//error_reporting(E_ERROR);
		require_once "../vendor/weixin/WxPay.Api.php";
		require_once "../vendor/weixin/WxPay.JsApiPay.php";
		require_once "../vendor/weixin/log.php";

		//初始化日志
		$logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
		$log = \Log::Init($logHandler, 15);

		//订单号获取
		$request=request();
		$order_number=$request->param('order_number/s');
		
		//①、获取用户openid
		$tools = new \JsApiPay();
		$user = session('user');
		isset($user['openid']) && $openId = $user['openid'];
		//如果为空 ，那么去微信信息表再次查询
		if(empty($openId) && $user['a_id']){
			$weixin=model('Weixinusers');
			$userInfo=$weixin->where('agent_id='.$user['a_id'])->field('openid')->find();
			$openId=$userInfo['openid'];
		}

		$type=0;
		$orderInfo=array();
		//先去订单表查询
		$order=model('Orders');
		$orderInfo=$order->where("order_number='".$order_number."'")->find();
		
		$agent_total_price=$ptotal_price=0;
		if(!empty($orderInfo)) {
			$orderList=$order->where("order_number='".$order_number."'")->select();
			$locationUrl=url("/index/Order/myOrders");
			
			foreach($orderList as $k=>$v) {
    			$agent_total_price+=get_agent_decrease_price_by_role($v['pid'],$v['pnumber'],$user['role']);//折扣
    			$ptotal_price+=$v['ptotal_price'];//商品总额
    		}
    		$orderInfo['agent_total_price']=$agent_total_price;//折扣
    		$orderInfo['ptotal_price']=$ptotal_price;//商品总额
		}
		$pay_type=1;//商品支付

		//如果没有数据，那么去礼包订单表查
		if(empty($orderInfo)) {
			$giftOrder=model('Promotiongiftorder');
			$giftOrderInfo=$giftOrder->where("order_number='".$order_number."'")->find();

			if(!empty($giftOrderInfo)) {
				//下单必备参数
				$orderInfo=$orderList=array();
				$orderInfo['pname']=$giftOrderInfo->gift->name;
				$orderInfo['order_amount_pay']=$giftOrderInfo['order_price'];
				$orderInfo['order_number']=$order_number;
				$orderInfo['create_time']=$giftOrderInfo['create_time'];


				$orderList=$giftOrder->where("order_number='".$order_number."'")->limit(1)->select();
				foreach($orderList as $k=>$v){
					$v['pname']=$giftOrderInfo->gift->name;
					$v['pprice']=$giftOrderInfo->gift->price;
				}

				$pay_type=2;//礼包支付

				$locationUrl=url('/index/Order/promotionGiftOrders');
			}
		}


		//如果没有，去充值表查询
		if(empty($orderInfo)) {
			//先去充值表查询
			$chargeorder=model('Agentstockchargeorder');
			$chargeorderInfo=$chargeorder->where("order_number='".$order_number."'")->find();

			//下单必备参数
			$orderInfo=$orderList=array();
			$orderInfo['pname']='库存充值'.$chargeorderInfo['order_amount_pay'];
			$orderInfo['order_amount_pay']=$chargeorderInfo['order_amount_pay'];
			$orderInfo['order_number']=$order_number;
			$orderInfo['create_time']=$chargeorderInfo['create_time'];

			$orderList=$chargeorder->where("order_number='".$order_number."'")->limit(1)->select();
			foreach($orderList as $k=>$v){
				$v['pname']='充值库存'.$v['order_amount_pay'];
				$v['pprice']=$v['order_amount_pay'];
				$v['pnumber']=1;
			}

			$locationUrl=url("/index/Person/rechargestock");
			$pay_type=3;//库存充值记录
		}

		// 2018-07-30 CYL 发送支付类型
		$this->assign('ptype',$pay_type);

		$this->assign('locationUrl',$locationUrl);
		$this->assign('orderList',$orderList);

		//改价后订单号更好了，如果是刷新，那么跳转到订单页
		if(empty($orderInfo))
		{
			$this->redirect('Order/myOrders');
		}

		//校验支付时间,超时校验
		$nowtime=time();
		 
		if($nowtime>strtotime($orderInfo['create_time'])+60*60) {
			$url=config('web.domain');
			//设置订单为取消状态
			if($pay_type==1) {
				 
				$odata=array();
				$odata['order_status']=7;//已取消
				$order->save($odata,array('order_number'=>$order_number));
				$url=url('/index/Order/myOrders');
			}
			if($pay_type==2) {
				 
				$data=array();
				$data['status']=7;//已取消
				$giftOrder->save($data,array('order_number'=>$order_number));
				$url=url('/index/Order/promotionGiftOrders');
			} 
			header('Content-Type:text/html; charset=utf-8');
			echo "订单已超时，请重新下单";
			echo "<a href='".$url."'>返回</a>";
			exit;
		}
		
		//下订单必备的信息
		if($order_number && $openId && $orderInfo['pname'] && $orderInfo['order_amount_pay']) {


			//②、统一下单
			$input = new \WxPayUnifiedOrder();
			$input->SetBody("购买商品");


			$input->SetOut_trade_no($order_number);
			$input->SetTotal_fee($orderInfo['order_amount_pay']*100);
			$input->SetTime_start(date("YmdHis"));
			$input->SetTime_expire(date("YmdHis", time() + 60*60));

			$input->SetNotify_url(config('web.domain')."index/Pay/wechatNotify/");
			$input->SetTrade_type("JSAPI");
			$input->SetOpenid($openId);


			$order = \WxPayApi::unifiedOrder($input);
			$jsApiParameters = $tools->GetJsApiParameters($order);

			$this->assign('jsApiParameters',$jsApiParameters);


			$pay=model('Weixinpay');
			$count=$pay->where("out_trade_no='".$order_number."'")->count();

			if($count==0)
			{
				$data=array();
				$data['out_trade_no']=$order_number;
				$data['status']=1;
				$data['create_time']=time();
				$data['sign']=$order['sign'];
				$data['openid']=$openId;
				$data['pay_type']=$pay_type;
				$data['total_fee']=$orderInfo['order_amount_pay']*100;
				$data['trade_type']=$order['trade_type'];
				$data['notify_url']=config('web.domain').'index/Pay/wechatNotify/';
				$pay->save($data);
			}

			$this->assign('orderInfo',$orderInfo);

	    	return $this->fetch();
		} else {

			echo '订单错误，请重新下单';
		}

    }


    public function wechatNotify()
    {
    	$request=request();

    	ini_set('date.timezone','Asia/Shanghai');
    	error_reporting(E_ERROR);

    	require_once "../vendor/weixin/WxPay.Api.php";
    	require_once '../vendor/weixin/WxPay.Notify.php';
    	require_once '../vendor/weixin/log.php';
    	require_once '../vendor/weixin/PayNotifyCallBack.php';

    	//初始化日志
    	$logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
    	$log = \Log::Init($logHandler, 15);


    	\Log::DEBUG("begin notify");
    	$notify = new \PayNotifyCallBack();

    	//存储微信的回调
    	$xml = $GLOBALS['HTTP_RAW_POST_DATA'];

    	$postObj= simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);


    	$out_trade_no	= $postObj->out_trade_no;
    	$openid=$postObj->openid;//openid
    	$sign=$postObj->sign;//校验码
    	$total_fee=$postObj->total_fee;//订单金额
    	$transaction_id=$postObj->transaction_id;//微信支付号


    	$pay=model('Weixinpay');
    	$count=$pay->where(array('out_trade_no'=>$out_trade_no))->count();
    	$log->INFO($pay->getLastSql());
    	if($count==1)
    	{
    		$info=$pay->where(array('out_trade_no'=>$out_trade_no))->field('id,openid,total_fee,pay_type')->find();
    		$log->INFO($pay->getLastSql());
    		if($info['openid']==$openid && $total_fee==$info['total_fee']) {
    			//更新微信支付表
    			$data=array();
    			$data['update_time']=time();
    			$data['status']=2;
    			$data['transaction_id']=$transaction_id;
    			$pay->save($data,array('out_trade_no'=>$out_trade_no,'openid'=>$openid));
    			$log->INFO($pay->getLastSql());


    			//校验下 是否是订单表的
    			//更新订单表
    			if($info['pay_type']==1) {
	    			$order=model('Orders');
	    			$count=$order->where("order_number='".$out_trade_no."'")->count();
	    			if($count>0){
		    			$odata=array();
		    			$odata['order_status']=2;//待发货
		    			$odata['pay_time']=time();
		    			$odata['pay_id']=$info['id'];
		    			$order->save($odata,array('order_number'=>$out_trade_no));
		    			$log->INFO($order->getLastSql());

		    			//商品订单支付完成后，提醒获取收益的人
		    			send_weixin_message_by_order_number($out_trade_no);
	    			}
    			}

    			//校验下 是否是礼包订单表的
    			if($info['pay_type']==2) {
	    			$giftOrder=model('Promotiongiftorder');
	    			$gcount=$giftOrder->where("order_number='".$out_trade_no."'")->count();
	    			if($gcount>0){
	    				$gdata=array();
	    				$gdata['status']=2;//待发货
	    				$gdata['pay_time']=time();
	    				$gdata['pay_id']=$info['id'];
	    				$giftOrder->save($gdata,array('order_number'=>$out_trade_no));
	    				$log->INFO($giftOrder->getLastSql());
	    			}
    			}


    			//校验下是否是库存充值表的
				if($info['pay_type']==3)
				{
					$order=model('Agentstockchargeorder');
					$count=$order->where("order_number='".$out_trade_no."'")->count();
					$log->INFO($order->getLastSql());
					if($count>0){

						//支付订单信息
						$chargeInfo=$order->where("order_number='".$out_trade_no."'")->field('agent_id,order_amount_pay,order_number')->find();
						$log->INFO($order->getLastSql());

						//充值户个人信息
						$agents=model('Agents');
						$agentInfo=$agents->find($chargeInfo['agent_id']);

						$odata=array();
						$odata['status']=2;//支付成功
						$odata['pay_time']=time();
						$odata['pay_id']=$info['id'];
						$order->save($odata,array('order_number'=>$out_trade_no));
						$log->INFO($order->getLastSql());

						//校验下是否已充值
						$stockchange=model('Agentstockchange');
						$stockCount=$stockchange->where("order_number='".$out_trade_no."'")->count();
						if($stockCount==0) {
							//插入一条充值变动记录
							
							$gdata=array();
							$gdata['agent_id']=$chargeInfo['agent_id'];
							$gdata['create_time']=time();
							$gdata['audit_time']=time();
							$gdata['change_before']=$agentInfo['stock_money'];
							$gdata['money']=$chargeInfo['order_amount_pay'];
							$gdata['status']=2;
							$gdata['remark']='微信支付在线充值';
							$gdata['change_type']=1;
							$gdata['account_type']=1;//微信
							$gdata['order_number']=$chargeInfo['order_number'];
							$gdata['change_after']=round($agentInfo['stock_money']+$chargeInfo['order_amount_pay'],2);
							$result=$stockchange->save($gdata);
							$log->INFO($stockchange->getLastSql());
							
							$chargeId=$stockchange->id;
	
							//给用户增加库存
							$agents->where('agent_id='.$agentInfo['agent_id'])->setInc('stock_money',$gdata['money']);
							$log->INFO($agents->getLastSql());
							
							if($chargeId) {
								//充值库存，上级产生收益
								$log->INFO('充值奖励-代理收益结果计算充值ID:'.$chargeId);
								agent_charge_stock_provide_profit($chargeId);
								agent_reward_recommend_no_ordernumber($chargeId,2);
				 
							}
							
						}
						
						
					}
				}

    			//回调结果
    			$res=$notify->Handle(true);
    			$log->INFO($res);
    		}
    	}
    	
    	//$log->INFO($pay->getLastSql());
    	$notify->Handle(true);
    	$log->INFO($res);

    }


    //微信支付订单退款
    public function refund($order_number)
    {
    	ini_set('date.timezone','Asia/Shanghai');
    	error_reporting(E_ERROR);
        require_once "../vendor/weixin/WxPay.Api.php";
    	require_once '../vendor/weixin/log.php';

    	//初始化日志
    	$logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
    	$log = \Log::Init($logHandler, 15);

    	$request=request();

    	if($order_number) {
    		$orderInfo=array();
    		$order=model('index/Orders');
    		$orderInfo=$order->where("order_number='".$order_number."'")->field('order_number,order_amount_pay')->find();

    		//如果订单表
    		if(empty($orderInfo)) {
    			$giftOrder=model('index/Promotiongiftorder');
    			$giftOrderInfo=$giftOrder->where("order_number='".$order_number."'")->field('order_number,order_price')->find();
    			!empty($giftOrderInfo['order_number']) && $orderInfo['order_number']=$giftOrderInfo['order_number'];
    			!empty($giftOrderInfo['order_price']) && $orderInfo['order_amount_pay']=$giftOrderInfo['order_price'];
    		}

    		if(!empty($orderInfo)) {

    			$refundInfo=array();
		    	$refundInfo['out_trade_no']=$orderInfo['order_number'];
		    	$refundInfo["total_fee"]=$orderInfo['order_amount_pay']*100;
		    	$refundInfo["refund_fee"]=$orderInfo['order_amount_pay']*100;
		    	$refundInfo['out_refund_no']=\WxPayConfig::MCHID.date("YmdHis");
				$refundInfo['status']=1;
				$refundInfo['create_time']=time();

				//相关数据插入到 微信退款记录表
			 	$refund=model('index/Weixinpayrefund');
			 	$refund->save($refundInfo);
			 	$payRefundId=$refund->id;

			 	$log->INFO($refund->getLastSql());


		    	if(isset($refundInfo) && $refundInfo != ""){

		    		$input = new \WxPayRefund();

		    		$input->SetOut_trade_no($refundInfo['out_trade_no']);
		    		$input->SetTotal_fee($refundInfo["total_fee"]);
		    		$input->SetRefund_fee($refundInfo["refund_fee"]);
		    		$input->SetOut_refund_no($refundInfo['out_refund_no']);

		    		$input->SetOp_user_id(\WxPayConfig::MCHID);
		    		$res=\WxPayApi::refund($input);

		    		if($res['return_code']=='SUCCESS') {

		    			$sign           =$res['sign'];//校验码

		    			$total_fee      =$res['total_fee'];//标价金额
		    			$refund_fee     =$res['refund_fee'];//退款金额

		    			$out_refund_no  =$res['out_refund_no'];// 商户退款单号
		    			$out_trade_no	=$res['out_trade_no'];//商户订单号

		    			$transaction_id =$res['transaction_id'];//微信订单号
		    			$refund_id      =$res['refund_id'];


		    			//先更新 微信退款表

		    			$data=array();
		    			$data['update_time']=time();
		    			$data['status']=2;
		    			$data['refund_id']=$refund_id;
		    			$data['transaction_id']=$transaction_id;
		    			$data['return_code']=$res['return_code'];
		    			$data['return_msg']=$res['return_msg'];
		    			$data['sign']=$sign;

		    			$refund->save($data,array('out_trade_no'=>$out_trade_no,'out_refund_no'=>$out_refund_no,'refund_fee'=>$refund_fee));
		    			$log->INFO($refund->getLastSql());

		    			//然后查找数据，更新 退款申请表
		    			$orderRefund=model('index/Agentorderrefund');
		    			$orderRefundInfo=$orderRefund->where(array('order_number'=>$order_number,'refund_fee'=>round($refund_fee/100,2)))->field('type,id')->find();
		    			$log->INFO($orderRefund->getLastSql());

		    			$rdata=array();
		    			$rdata['refund_time']=time();
		    			$rdata['refund_status']=2;
		    			$rdata['refund_pay_id']=$payRefundId;

		    			$orderRefund->save($rdata,array('order_number'=>$order_number,'refund_fee'=>round($refund_fee/100,2)));
		    			$orderRefundId=$orderRefundInfo['id'];

		    			$log->INFO($orderRefund->getLastSql());

		    			//然后更新订单表和礼包订单
		    			if($orderRefundInfo['type']==1){
			    			$odata=array();
			    			$odata['refund_time']=time();
			    			$odata['order_refund_id']=$orderRefundId;

			    			$order->save($odata,array('order_number'=>$order_number));
			    			$log->INFO($order->getLastSql());
		    			} else {

		    				$odata=array();
		    				$odata['refund_time']=time();
		    				$odata['order_refund_id']=$orderRefundId;
		    				$giftorder=model('index/Promotiongiftorder');
		    				$order->save($odata,array('order_number'=>$order_number));
		    				$log->INFO($order->getLastSql());

		    			}

		    		} else {

		    			//更新支付表
		    			$data=array();
		    			$data['return_code']=$res['return_code'];
		    			$data['return_msg']=$res['return_msg'];

		    			$refund->save($data,array('id'=>$payRefundId));
		    			$log->INFO($refund->getLastSql());

		    			//更新退款申请表
		    			$orderRefund=model('index/Agentorderrefund');

		    			$odata=array();
		    			$odata['refund_status']=3;
		    			$odata['refund_time']=time();

		    			$orderRefund->save($odata,array('order_number'=>$order_number));
		    			$log->INFO($orderRefund->getLastSql());

		    		}


		    	}
    		}

    	} else {

    		echo '订单错误，请重试';
    	}

    }

    /**
     * 库存支付
     *
     * @return \think\Response
     */
    public function stock()
    {
    	if(!session('?user'))
    	{
    		$this->redirect('Index/login');
    	}

    	$request=request();
    	$order_number=$request->param('order_number/s');


    	$user=session('user');
    	
    	$orderInfo=array();
    	//先去订单表查询
    	$order=model('Orders');
    	$orderInfo=$order->where("order_number='".$order_number."'")->find();
    	
    	$agent_total_price=$ptotal_price=0;
    	if(!empty($orderInfo)) {
    		$orderList=$order->where("order_number='".$order_number."'")->select();
    		foreach($orderList as $k=>$v) {
    			$agent_total_price+=get_agent_decrease_price_by_role($v['pid'],$v['pnumber'],$user['role']);//折扣
    			$ptotal_price+=$v['ptotal_price'];//商品总额
    		}
    		$orderInfo['agent_total_price']=$agent_total_price;//折扣
    		$orderInfo['ptotal_price']=$ptotal_price;//商品总额
    	}


    	//如果没有数据，那么去礼包订单表查
    	if(empty($orderInfo)) {
    		$giftOrder=model('Promotiongiftorder');
    		$giftOrderInfo=$giftOrder->where("order_number='".$order_number."'")->find();

    		if(!empty($giftOrderInfo)) {
    			//下单必备参数
    			$orderInfo=$orderList=array();
    			$orderInfo['pname']=$giftOrderInfo->gift->name;
    			$orderInfo['order_amount_pay']=$giftOrderInfo['order_price'];
    			$orderInfo['order_number']=$order_number;


    			$orderList=$giftOrder->where("order_number='".$order_number."'")->limit(1)->select();
    			foreach($orderList as $k=>$v){
    				$v['pname']=$giftOrderInfo->gift->name;
    				$v['pprice']=$giftOrderInfo->gift->price;
    			}

    		}
    	}


    	//改价后订单号更好了，如果是刷新，那么跳转到订单页
    	if(empty($orderInfo))
    	{
    		$this->redirect('Order/myOrders');
    	}


    	$agent=model('index/Agents');
    	$agentInfo=$agent->find($user['a_id']);

    	$this->assign('agentInfo',$agentInfo);
    	$this->assign('orderList',$orderList);
    	$this->assign('orderInfo',$orderInfo);

    	return $this->fetch();
    }

    //扣减库存
    public function actionStock()
    {
    	if(!session('?user'))
    	{
    		$this->redirect('Index/login');
    	}

    	//订单号获取
    	$request=request();
    	$order_number=$request->param('order_number/s');


    	//订单表查询
		$order=model('Orders');
		$orderInfo=$order->where("order_number='".$order_number."'")->find();
		$pay_type=1;//商品支付


		//如果没有数据，那么去礼包订单表查
		if(empty($orderInfo)) {
			$giftOrder=model('Promotiongiftorder');
			$giftOrderInfo=$giftOrder->where("order_number='".$order_number."'")->find();

			$pay_type=2;//礼包支付
		}

		//减库存，更改订单状态，库存变更记录
		$result=0;
		$result=agent_order_stock_pay($order_number,$pay_type);

		//商品订单支付完成后，提醒获取收益的人
		//if($pay_type==1) {
			//send_weixin_message_by_order_number($order_number);
		//}

		//如果是商品
		if($result==1 && $pay_type==2) {
			$result=2;
		}

		echo json_encode($result);

    }

     //查询微信退款状态
    public function refundQuery($order_number)
    {
    	$res=0;

    	ini_set('date.timezone','Asia/Shanghai');
    	error_reporting(E_ERROR);
    	require_once "../vendor/weixin/WxPay.Api.php";
    	require_once '../vendor/weixin/log.php';


    	//校验下订单号
    	//先校验商品的
    	if($order_number) {
    		$orderInfo=array();
	    	$order=model('index/Orders');
			$orderInfo=$order->where("order_number='".$order_number."'")->find();

			if(empty($orderInfo)) {
				$giftOrder=model('index/Promotiongiftorder');
				$orderInfo=$giftOrder->where("order_number='".$order_number."'")->find();
			}

			if(empty($orderInfo)){
				return $res;
			}

			$input = new \WxPayRefundQuery();
			$input->SetOut_trade_no($order_number);
		    $res=\WxPayApi::refundQuery($input);
    	}

    	return $res;
    }

    //查询微信支付状态
    public function payQuery($order_number)
    {
    	$res=0;

    	ini_set('date.timezone','Asia/Shanghai');
    	error_reporting(E_ERROR);
    	require_once "../vendor/weixin/WxPay.Api.php";
    	require_once '../vendor/weixin/log.php';

    	//校验下订单号
    	//先校验商品的
    	if($order_number) {
	    	$orderInfo=array();
	    	$order=model('index/Orders');
	    	$orderInfo=$order->where("order_number='".$order_number."'")->find();

	    	if(empty($orderInfo)) {
	    		$giftOrder=model('index/Promotiongiftorder');
	    		$orderInfo=$giftOrder->where("order_number='".$order_number."'")->find();
	    	}

	    	if(empty($orderInfo)){
	    		return $res;
	    	}

	 		$input = new \WxPayOrderQuery();
			$input->SetTransaction_id($order_number);
			$res= \WxPayApi::orderQuery($input);
    	}
    	return $res;
    }

}