<?php

namespace app\admin\controller;

use think\Request;

class Promotiongiftorder extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$order=model('Promotiongiftorder');
    	
    	$data=array();
    	$request=request();
    	
    	$status=$agent_id=$order_number=$stime=$etime=$consignee_name=$consignee_phone=$name=$phone=$gift_name=$gift_type=0;
    	
    	//订单表数据获取
    	$request->param('status') && $status=$request->param('status');
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	
    	$request->param('stime') && $stime=$request->param('stime');
    	$request->param('etime') && $etime=$request->param('etime');
    	
    	//收货人姓名和 手机号
    	$request->param('consignee_name') && $consignee_name=$request->param('consignee_name');
    	$request->param('consignee_phone') && $consignee_phone=$request->param('consignee_phone');
    	
    	//下单人姓名和 手机号
    	$request->param('name') && $name=$request->param('name');
    	$request->param('phone') && $phone=$request->param('phone');
    	
    	$request->param('gift_name') && $gift_name=$request->param('gift_name');
    	$request->param('gift_type') && $gift_type=$request->param('gift_type');
    	
    	//查询匹配
    	$status>0 && $data['promotion_gift_order.status']=$status;
    	!empty($agent_id) && $data['promotion_gift_order.agent_id']=$agent_id;
    	!empty($order_number) && $data['order_number']=$order_number;
    	
    	//收货信息
    	!empty($consignee_name) && $data['consignee_name']=['like','%'.$consignee_name.'%'];
    	!empty($consignee_phone) && $data['consignee_phone']=$consignee_phone;
    	
    	$cdata=array();
    	//下单人信息
    	!empty($name) && $cdata['agents.name']=['like','%'.$name.'%'];
    	!empty($phone) && $cdata['agents.phone']=$phone;
    	 
    	
    	!empty($gift_name) && $data['promotion_gift_info.name']=['like','%'.$gift_name.'%'];
    	!empty($gift_type) && $data['promotion_gift_info.type']=$gift_type;
    	
    	//time
    	$stime>0 && $data['promotion_gift_order.create_time']=['>=',strtotime($stime)];
    	if($etime>0){
    		$endtime=strtotime($etime)+60*60*24;
    		$data['promotion_gift_order.create_time']=['<=',$endtime];
    	}
    	if(!empty($stime) && !empty($endtime)) {
    		$data['promotion_gift_order.create_time']=['between time',[$stime,$endtime]];
    	}
    	
    	//订单状态
    	$orderStatus=$order->status;
    	$this->assign('orderStatus',$orderStatus);
    	
     
    	//已删除状态的不在显示
    	$orderList=$order->hasWhere('agents',$cdata)
    	->join('promotion_gift_info','promotion_gift_info.id=promotion_gift_order.gift_id')
    	->where($data)
    	->order('id','desc')
    	->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
    	
    	foreach($orderList as $k=>&$v)
    	{
    		//如果是已取消的订单并且在线支付，查看退款状态
    		if($v->getData('status')==7 and $v->getData('paystyle')==2){
    			$refund=model('Agentorderrefund');
    			$v['refund_status']=$refund->getAuthStatusByOrderNumber($v['order_number']);
    		}
    		 
    	}
     
    	
    	$this->assign('orderList',$orderList);
     
    	
    	//等级列表
    	$level=model('Agentlevel');
    	$listLevel=$level->where(['valid'=>1])->order('id','asc')->select();
    	$this->assign('listLevel',$listLevel);
    	
    	//搜索条件赋值
    	$this->assign('order_number',$order_number);
    	$this->assign('agent_id',$agent_id);
    	$this->assign('status',$status);
    	
    	$this->assign('consignee_name',$consignee_name);
    	$this->assign('consignee_phone',$consignee_phone);
    	
    	$this->assign('name',$name);
    	$this->assign('phone',$phone);
    	
    	
    	$this->assign('gift_name',$gift_name);
    	$this->assign('gift_type',$gift_type);
    	
    	$this->assign('stime',$stime);
    	$this->assign('etime',$etime);
    	
    	return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
    	$provinces = model('BasicDataAddress')->getProviceList();
    	$this->assign('provinces',$provinces);
    	
    	//礼包列表
    	$giftList=model('Promotiongift')->select();
    	$this->assign('giftList',$giftList);
    	
    	return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
       $data=array();
       
       $data['gift_id']=$request->param('gift_id');
       $data['pnumber']=$request->param('pnumber');
       
       $data['consignee_province']=$request->param('consignee_province');
       $data['consignee_city']=$request->param('consignee_city');
       $data['consignee_area']=$request->param('consignee_area');
       $data['consignee_address']=$request->param('consignee_address');
       
       $data['consignee_name']=$request->param('consignee_name');
       $data['consignee_phone']=$request->param('consignee_phone');
       $data['agent_id']=$request->param('agent_id');
       
       $data['create_time']=time();
       $data['status']=1;
       
       $order=model('Promotiongiftorder');
       $gift=model('Promotiongift');
       
       !empty($data['agent_id']) && $data['order_number']=$order->getOrderNumber($data['agent_id']);
       
       //获取礼包的价格和数量，得到要支付的金额
       $giftInfo=$gift->find($data['gift_id']);
       $data['order_price']=$giftInfo['price']*$data['pnumber'];
       
       $res=$order->save($data);
       
       if($res) {
       		//如果添加成功 礼包库存减一  销量加1
       		$gift=model('Promotiongift');

       		//先校验下 库存是否充足
       		if($giftInfo['number']>=$data['pnumber']) {
       		
	       		$gift->where(array('id'=>$data['gift_id']))->setInc('sale',$data['pnumber']);
	       		$gift->where(array('id'=>$data['gift_id']))->setDec('number',$data['pnumber']);
		 	
	       		$this->success('添加成功','/admin/Promotiongiftorder/index/');
       		} else {
       			$this->success('添加失败，礼包库存不足','/admin/Promotiongiftorder/index/');
       		}
       }
       
    }
 
    //查看订单详情
    public function read($order_number)
    {
    	$giftOrder=model('Promotiongiftorder');
    	$orderReward=model('Promotiongiftorderreward');
    	$finan=model('AgentFinancialAccount');
    	
    	//订单详情
    	$ordersInfo=$giftOrder->where(array('order_number'=>$order_number))->find();
    	
    	//增加校验，支付状态
    	if($ordersInfo->getData('paystyle')==2) {
    		$accountInfo=$finan->where("a_id='".$ordersInfo['agent_id']."' and type=3")->find();
    		$ordersInfo['wechatAccount']=$accountInfo['account'];
    	}
    	
    	$this->assign('ordersInfo',$ordersInfo);
    	
    	//订单奖励
    	$ordersReward=$orderReward->where(array('order_number'=>$order_number))->select();
    	
    	//如果没有奖励，那么去奖励表计算下
    	$type=0;
    	if(empty($ordersReward)) {
    		$type=1;
    		$ordersReward=promotion_gift_reward($order_number,2);
    		
    		$agents=model('Agents');
    		if(!empty($ordersReward)) {
	    		foreach($ordersReward as $k=>&$v) {
	    			$tmp=$agents->getAgents($v['agent_id']);
	    			$v['name']=$tmp['name'];
	    			$v['rolename']=get_reward_levelname($tmp['role']);
	    		}
    		}
    	}
    	
    	$this->assign('ordersReward',$ordersReward);
    	$this->assign('type',$type);
    	
    	//获取发货公司的列表
    	$delivery=model('Agentorderdelivery');
    	$expressComList=$delivery->expressName;
    	$this->assign('expressComList',$expressComList);
    	
    	
    	return $this->fetch();
    }
    
    //订单发货和删除关闭状态
    public function update()
    {
    	$res=0;
    	
    	$request=request();
    	$id=$request->param('order_number/s');
    	$status=$request->param('status');
    	
    	if($id && $status) {
	    	$giftOrder=model('Promotiongiftorder');
	    	$data=array();
	    	$data['order_number']=$id;
	    	$data['status']=$status;
	    	$status==5 && $data['complete_time']=time();
	    	$giftOrder->save($data,array('order_number'=>$data['order_number']));
	  
	    	$res=1;
    	}
    	
    	return $res;
    }
    
    //发货 1是页面发货 2是弹窗发货
    public function express($type=1)
    {
    	$request=request();
    	
    	$delivery=model('Agentorderdelivery');
    	$express_company=$delivery->expressName;//名称结果数组
 
    	$data=array();
    	$data['order_number']=$request->param('order_number');
    	$data['express_code']=$request->param('express_code');
    	
    	$data['express_name']=$express_company[$data['express_code']];
   
    	$data['express_number']=$request->param('express_number');
    	$data['express_remark']=$request->param('express_remark');
 
    	if($data['order_number'] && $data['express_name'] && $data['express_number'])
    	{
    		$giftorder=model('Promotiongiftorder');
    		$data['delivery_time']=time();
    		$data['status']=3;
    		
    		//大礼包订单发货，同事变更人员身份
    		$orderInfo=$giftorder->where("order_number='".$data['order_number']."'")->find();
     
    		if(!empty($orderInfo)){
    	 
    			$agentInfo=model('Agents')->find($orderInfo['agent_id']);
    			$giftInfo=model('Promotiongift')->find($orderInfo['gift_id']);
    			if($giftInfo['type']>$agentInfo['role']) {
    			 
    				$apply=model('AgentApplications');//插入一条申请记录
    				$ndata=array();
    				$ndata['a_id']=$agentInfo['agent_id'];
    				$ndata['type']=5;
    				$ndata['create_ctime']=date('Y-m-d H:i:s');
    				$ndata['target']=$giftInfo['type'];
    				$ndata['status']=1;
    				$ndata['examiner']=session('username');
    				$ndata['examine_etime']=date('Y-m-d H:i:s');
    				$ndata['remarks']='购买大礼包升级';
    				$apply->save($ndata);
    			 
    				//然后更改用户的等级
    				model('Agents')->where(['agent_id'=>$agentInfo['agent_id']])->update(['is_use'=>1,'role'=>$giftInfo['type'],'status'=>3]);
    			
    				//查询是否有申请记录，如果有就驳回
    				if(in_array($agentInfo['status'],array(1,2))) {
    					model('AgentApplications')->where(['a_id'=>$agentInfo['agent_id'],'status'=>0])->update(['status'=>3,'examine_etime'=>date("Y-m-d H:i:s"),'remarks'=>'购买礼包升级','examiner'=>session('username')]);
    				}
    				
    				message_notification(1,$agentInfo['agent_id'],array('name'=>'升级大礼包','remark'=>'您购买的升级大礼包已发货，请重新登录获取新的身份'));
    				
    			}
    			
    			//计算奖励信息
    			promotion_gift_reward($data['order_number']);
    		 
    		}
    		
    		$res=$giftorder->isUpdate()->save($data,array('order_number'=>$data['order_number']));
    	}
    	
    	if($type==2) {
    		if($res==0){
    			return json_encode(['code'=>'-1','msg'=>'发货失败']);exit;
    		}else{
    			return json_encode(['code'=>'0','msg'=>'发货成功']);exit;
    		}
    	}
    	
    	$this->success('操作成功', '/admin/Promotiongiftorder/index/');
    	
    }
    

    //保存订单备注
    public function saveOrderMark()
    {
    	$request = request();
    	$res=0; 
    	
    	//数据获取
    	$order_number=$request->param('order_number');
    	$mark=$request->param('order_remark');

    	if($order_number && $mark) {
    	
	    	$data=array();
	    	$data['order_remark']=$mark;
	    	 
	    	$orders=model('Promotiongiftorder');
	    	$orders->save($data,array('order_number'=>$order_number));
	    	 
	    	$res=1;
    	}
    	
    	return $res;
    }
    
    //首页搜索导出
    public function excelIndex()
    {
    	$order=model('Promotiongiftorder');
    	 
    	$data=array();
    	$request=request();
    	 
    	$status=$agent_id=$order_number=$stime=$etime=$consignee_name=$consignee_phone=$name=$phone=$gift_name=$gift_type=0;
    	 
    	//订单表数据获取
    	$request->param('status') && $status=$request->param('status');
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('order_number') && $order_number=$request->param('order_number');
    	 
    	$request->param('stime') && $stime=$request->param('stime');
    	$request->param('etime') && $etime=$request->param('etime');
    	 
    	//收货人姓名和 手机号
    	$request->param('consignee_name') && $consignee_name=$request->param('consignee_name');
    	$request->param('consignee_phone') && $consignee_phone=$request->param('consignee_phone');
    	 
    	//下单人姓名和 手机号
    	$request->param('name') && $name=$request->param('name');
    	$request->param('phone') && $phone=$request->param('phone');
    	 
    	$request->param('gift_name') && $gift_name=$request->param('gift_name');
    	$request->param('gift_type') && $gift_type=$request->param('gift_type');
    	 
    	//查询匹配
    	$status>0 && $data['promotion_gift_order.status']=$status;
    	!empty($agent_id) && $data['promotion_gift_order.agent_id']=$agent_id;
    	!empty($order_number) && $data['order_number']=$order_number;
    	 
    	//收货信息
    	!empty($consignee_name) && $data['consignee_name']=['like','%'.$consignee_name.'%'];
    	!empty($consignee_phone) && $data['consignee_phone']=$consignee_phone;
    	 
    	$cdata=array();
    	//下单人信息
    	!empty($name) && $cdata['agents.name']=['like','%'.$name.'%'];
    	!empty($phone) && $cdata['agents.phone']=$phone;
    	
    	 
    	!empty($gift_name) && $data['promotion_gift_info.name']=['like','%'.$gift_name.'%'];
    	!empty($gift_type) && $data['promotion_gift_info.type']=$gift_type;
    	 
    	//time
    	$stime>0 && $data['promotion_gift_order.create_time']=['>=',strtotime($stime)];
    	if($etime>0){
    		$endtime=strtotime($etime)+60*60*24;
    		$data['promotion_gift_order.create_time']=['<=',$endtime];
    	}
    	if(!empty($stime) && !empty($endtime)) {
    		$data['promotion_gift_order.create_time']=['between time',[$stime,$endtime]];
    	}
    	 
    	 
    	//已删除状态的不在显示
    	$orderList=$order->hasWhere('agents',$cdata)
    	->join('promotion_gift_info','promotion_gift_info.id=promotion_gift_order.gift_id')
    	->where($data)
    	->order('id','desc')
    	->select();
     
    	
    	//开始导出相关
    	$title=array('订单编号','订单状态','礼包名称','礼包类型','礼包售价','代理ID','下单人姓名','下单人电话','下单日期','收货人姓名','收货人电话','收货地址');
    	$filename='礼包订单记录';
    	
    	$data=array();
    	foreach($orderList as $k=>$v)
    	{
    		$data[$k]['order_number']="编号：".$v->order_number;
    		$data[$k]['status']=$v['status'];
    		
    		$data[$k]['gift_name']=$v->gift->name;
    		$data[$k]['gift_type']=get_reward_levelname($v->gift->type);
    		$data[$k]['gift_price']=$v->gift->price;
    		$data[$k]['agent_id']=$v->agent_id;
    		$data[$k]['agent_name']=$v->agents->name;
    		$data[$k]['agent_phone']=$v->agents->phone;
    		$data[$k]['create_time']=$v->create_time;
    		$data[$k]['consignee_name']=$v->consignee_name;
    		$data[$k]['consignee_phone']=$v->consignee_phone;
    		$data[$k]['consignee_address']=get_address_name_by_id($v->consignee_province).get_address_name_by_id($v->consignee_city).get_address_name_by_id($v->consignee_area).$v->consignee_address;
    	}
     
    	$this->exportexcel($data,$title,$filename);
    }

    
    //发货
    public function delivery(Request $request)
    {
    	$order_number=$request->param('order_number');
    	
    	$giftorder=model('Promotiongiftorder');
    	$orderInfo=$giftorder->where(array('order_number'=>$order_number))->find();
    	
    	$this->assign('orderInfo',$orderInfo);
    	$this->assign('order_number',$order_number);
    	
    	return $this->fetch();
    }
    
  
    //物流信息跟踪显示
    public function expressInfo($order_number)
    {
    	$giftOrder=model('Promotiongiftorder');
    	 
    	//订单详情
    	$orderInfo=$giftOrder->where(array('order_number'=>$order_number))->find();
    	$this->assign('orderInfo',$orderInfo);
    	 
    	//物流信息查询
    	$expressInfoUrl=0;
    	$expressInfoUrl=get_express_info($order_number,2);
    	$this->assign('expressInfoUrl',$expressInfoUrl);
    	
    	return $this->fetch('expressInfo');
    	 
    }
}
