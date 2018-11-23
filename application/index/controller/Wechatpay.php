<?php

namespace app\Index\controller;

use think\Controller;
use think\Request;

 
 

class Wechatpay extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	
		ini_set('date.timezone','Asia/Shanghai');
		//error_reporting(E_ERROR);
		require_once "../vendor/weixin/WxPay.Api.php";
		require_once "../vendor/weixin/WxPay.JsApiPay.php";
		require_once "../vendor/weixin/log.php";
 
		//初始化日志
		$logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
		$log = \Log::Init($logHandler, 15);
		
		
		
		//①、获取用户openid
		$tools = new \JsApiPay();
		$openId = 'oVWo-w7o4HIVmNhlHahSrKNitYpg';
		
		//②、统一下单
		$input = new \WxPayUnifiedOrder();
		$input->SetBody("test");
		$input->SetAttach("test");
		$oud_trade_no=\WxPayConfig::MCHID.date("YmdHis");
		$input->SetOut_trade_no($oud_trade_no);
		$input->SetTotal_fee("1");
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetGoods_tag("test");
		$input->SetNotify_url("http://ams.gongtuo.com/index/WechatPay/notify/");
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		
		
		$order = \WxPayApi::unifiedOrder($input);
		echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		$this->printf_info($order);
		$jsApiParameters = $tools->GetJsApiParameters($order);
		$this->assign('jsApiParameters',$jsApiParameters);

 
		$pay=model('Weixinpay');
		$count=$pay->where("out_trade_no='".$oud_trade_no."'")->count();
		if($count==0)
		{
			$data=array();
			$data['out_trade_no']=$oud_trade_no;
			$data['status']=1;
			$data['create_time']=time();
			$data['sign']=$order['sign'];
			$data['openid']='oVWo-w7o4HIVmNhlHahSrKNitYpg';
			$data['total_fee']=1;
			$data['trade_type']=$order['trade_type'];
			$data['notify_url']='http://ams.gongtuo.com/index/WechatPay/notify/';
			$pay->save($data);
		}
		
    	return $this->fetch();
    	
    }

    //打印输出数组信息
    function printf_info($data)
    {
    	foreach($data as $key=>$value){
    		echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    	}
    }
    
    public function notify()
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
    	
    	//根据订单号校验签名
    	//根据订单号校验订单金额
    	
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
    		$info=$pay->where(array('out_trade_no'=>$out_trade_no))->field('openid,total_fee')->find();
    		if($info['openid']==$openid && $total_fee==$info['total_fee']) {
    			$data=array();
    			$data['update_time']=time();
    			$data['status']=2;
    			$data['transaction_id']=$transaction_id;
    			$pay->save($data,array('out_trade_no'=>$out_trade_no,'openid'=>$openid));
    			$log->INFO($pay->getLastSql());
    			$res=$notify->Handle(true);
    			$log->INFO($res);
    		}
    	}
    	//$log->INFO($pay->getLastSql());
    	//$notify->Handle(true);
    
    }
}
