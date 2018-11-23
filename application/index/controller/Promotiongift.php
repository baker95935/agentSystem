<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Promotiongift extends Common
{
    /**
     * 首页
     * @return \think\Response
     */
    public function index()
    {
    	//根据登录用户获取用户的地址 信息
    	$user = session('user');
    	
    	$title="升级大礼包";
    	$this->assign('spider',['title'=>$title,'index'=>3]);

    	$gift=model('Promotiongift');

    	$giftList=$gift->where('number>0 and type>'.$user['role'])->order('type desc,id desc')->select();
    	$this->assign('giftList',$giftList);

    	return $this->fetch();
    }


    //详情
    public function detail($id)
    {
    	$title="升级大礼包";
    	$this->assign('title',$title);

    	$gift=model('Promotiongift');
    	$giftInfo=$gift->find($id);
    	$this->assign('giftInfo',$giftInfo);

    	return $this->fetch();
    }

    //订单确认页
    public function order()
    {
    	$title="礼包订单";
    	$this->assign('title',$title);

    	//根据登录用户获取用户的地址 信息
    	$user = session('user');

    	//查找地址表 获取默认的地址
    	$address=model('AgentAddress');
    	$addressInfo=$address->where(array('a_id'=>$user['a_id'],'is_del'=>0))->order('is_default desc')->find();
    	$this->assign('addressInfo',$addressInfo);

    	$request=request();
    	$id=$request->param('id');
    	
    	//根据礼包的ID 获取礼包数据
    	$gift=model('Promotiongift');
    	$giftInfo=$gift->find($id);
    	$this->assign('giftInfo',$giftInfo);
    	
    	//校验下  如果库存够，那么显示库存支付
    	$stockPay=0;
    	$agent=model('index/Agents');
    	$agentInfo=$agent->find($user['a_id']);
    	
    	$agentInfo['stock_money']>=$giftInfo['price'] && $stockPay=1;
    	$this->assign('stockPay',$stockPay);//库存支付

    	return $this->fetch();
    }

    //订单保存
    public function saveOrder()
    {
    	//获取地址ID
    	//获取礼包信息ID
    	$request=request();

    	//模型引入
    	$address=model('AgentAddress');
    	$gift=model('Promotiongift');

    	$addressId=$request->param('addressId');
    	$giftId=$request->param('giftId');
    	$paystyle=$request->param('paystyleVal');
    	$remark=$request->param('remark');

    	//信息获取
    	$addressInfo=$address->find($addressId);
    	$giftInfo=$gift->find($giftId);

    	$user=session('user');
    	$giftorder=model('Promotiongiftorder');

    	$result=0;
    	//先校验下 库存是否充足
    	if($giftInfo['number']>=1) {

	    	//礼包包更新销量
	    	$gift->where(array('id'=>$giftId))->setInc('sale',1);
	    	$gift->where(array('id'=>$giftId))->setDec('number',1);

	    	//大礼包订单表
	    	$data=array();
	    	$order_number=$data['order_number']=$giftorder->getOrderNumber($user['a_id']);

	    	$data['create_time']=time();
	    	$data['gift_id']=$giftInfo['id'];
	    	$data['agent_id']=$user['a_id'];

	    	//收货地址
	    	$data['consignee_name']=$addressInfo['name'];
	    	$data['consignee_phone']=$addressInfo['phone'];
	    	$data['consignee_province']=$addressInfo['province'];
	    	$data['consignee_city']=$addressInfo['city'];
	    	$data['consignee_area']=$addressInfo['area'];
	    	$data['consignee_address']=$addressInfo['address'];

	    	$data['pnumber']=1;
	    	$data['order_price']=$giftInfo['price'];
	    	$data['paystyle']=$paystyle;
	    	
	    	$data['status']=2;
	    	$data['paystyle']==2 && $data['status']=1;//如果是微信支付，那么状态设置为1
	    	$data['paystyle']==3 && $data['status']=1;//如果是库存支付，那么状态设置为1

	    	!empty($remark) && $data['remark']=$remark;

	    	$result=$giftorder->save($data);

    	}

    	if($result)
    	{

    		$orderInfo=$giftorder->field('order_price,paystyle')->where("order_number='".$order_number."'")->find();
    		//小于支付限额，并且选择的微信支付,并且有openid的

    		//在获取一次OPENID
    		$openid='';
    		isset($user['openid']) && $openid=$user['openid'];
    		if(!isset($user['openid'])) {
    			$weixin=model('Weixinusers');
    			$openid=$weixin->getUserOpenid($user['a_id']);
    		}
    		
    		$agent=model('index/Agents');
    		$agentInfo=$agent->find($user['a_id']);

    		if($orderInfo['order_price']<=config('web.pay_limit') && $orderInfo['paystyle']==2 && $openid) {

    			//跳转到支付页面
    			$this->redirect('Pay/wechat',"order_number=".$order_number);
    		} else if($orderInfo['paystyle']==3 && $agentInfo['stock_money']>=$orderInfo['order_price']) {
            	//跳转到库存支付页面
            	$this->redirect('Pay/stock',"order_number=".$order_number);
            } else {
    			$this->redirect('Order/promotionGiftOrders');
    		}

    	}else{
    		return ['error'=>['msg'=>'订单提交失败，请重试']];
    	}
    }


}
