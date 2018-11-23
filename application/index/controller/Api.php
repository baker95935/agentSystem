<?php

namespace app\Index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Session;

class Api extends Controller
{
	//思路整理
	//定义密码，根据密码生成accesstoken，然后每次请求验证token就行
	//初始化
 	public function _initialize()
    {
    	//定义密码和盐
        $password="gongtuo123";
        $salt="zhaoyun";
        
        $request=request();
        //数据获取
        $request_password=$request->param('password');
        $request_access_token=$request->param('access_token');
        
        $data=array();

        //为空的校验
        if(empty($request_password) && empty($request_access_token)) {
        	$data['code']=4001;
        	$data['message']='请输入参数';
        }
        
        //先用token进行判断，成功可继续不返回
        if(!empty($request_access_token)) {
        	$access_token=crypt($password,$salt);
        	if($request_access_token!=$access_token) {
        		$data['code']=4002;
        		$data['message']='access token验证失败，请使用密码重新请求';
        	}
        } else {
        	//然后用密码进行判断，成功返回
        	if(!empty($request_password)) {
	        	if($password!=$request_password) {
	        		$data['code']=4003;
	        		$data['message']='密码验证失败，请使用正确的密码重新请求';
	        	} else {
	        		$access_token=crypt($password,$salt);
	        		$data['code']=1;
	        		$data['message']='密码验证成功，返回access token';
	        		$data['data']=$access_token;
	        	}
        	}
        }
        
        //如果有数据，那么才返回
        if(!empty($data)) {
        	Response::create($data, 'jsonp')->send();
        	exit;
        }
       
    }
    
    //首页
    public function index()
    {
    	$this->checkLogin();
    	$agent_id=session('agent_id');
    	
    	$agent=model('Agents');
    	$info=$agent->getInfoByCondition(['agent_id'=>$agent_id,'is_del'=>0]);// 获取未删除代理商的信息
    	
    	$family   = $agent->getSons($info['agent_id'],$info['role'],1);// 直属成员ID(包含已删除/已失效的)
    	$family[] = $info['agent_id'];
    	
 
    	$dataInfo = [
	    	'team'   => count_team_orders_sales_total($family),// 团队业绩
	    	'me'     => count_team_orders_sales_total([$agent_id]),// 我的业绩
	    	'lower'  => count($agent->getSons($agent_id,$info['role'],4)),// 团队结构-下级(直属代理商未删除)
	    	'invite' => count($agent->getAgentAllSonsId(['inviter'=>$agent_id,'is_del'=>0,'is_use'=>1])),// 团队结构-推荐人数
	    	'member' => count($agent->getAgentAllSonsId(['inviter'=>$agent_id,'role'=>0,'is_del'=>0])),// 团队结构-会员
	    	'reward' => model('Agentprofit')->getRewardByDefined(['agent_id'=>$agent_id]),// 我的奖励
    	];
    	
    	$data=array();
    	$data['code']=1;
    	$data['message']="数据获取成功";
    	$data['data']=$dataInfo;
    	
    	Response::create($data, 'jsonp')->send();
    	exit;
    }
    
    //商品列表
    public function productList()
    {
    	$data=array();
    	
    	$request =request();
    	
    	//分页变量
    	$pagesize=2;
    	$page=$request->param('page');
    	
    	empty($page) && $page=1;
    	$limit=($page-1)*$pagesize;
    	
    	//数据获取
    	$product = model('Products');
    	$data['list'] = $product->field('id,product_name,category_id,classify_id,product_img,explain,sales_price,unit,inventory,sales_volume,create_time,is_first_order,false_volume')
    		->where('state=1')
    		->order('sales_volume DESC')
    		->limit($limit,$pagesize)
    		->select();
    	
    	$data['count']=$product->where('state=1')->count();
    	
    	
    	//循环取出销量值sales_volume为实际销量，false_volume为后台设置销量
    	foreach ($data['list'] as $k=>&$value){
    		$value['mix_volume'] = $value['false_volume']+$value['sales_volume'];
    	}
    	
    	//返回结果
    	Response::create(['data'=>$data], 'jsonp')->send();
    	exit;
    }
    
    
    //分类列表
    public function categoryList()
    {
    	$data=array();
    	
    	$category=model('ProductCategory');
    	
    	$data['list']=$category->where('parent_id=0')->select();
    	$data['count']=$category->where('parent_id=0')->count();
    	
    	//返回结果
    	Response::create(['data'=>$data], 'jsonp')->send();
    	exit;
    }
    
    
    //注册
    public function register()
    {
    	$request = request();
    	$agent = model('Agents');
    	
    	$data=array();
    	
    	$phone = $request->param('phone');
    	$info = $agent->where(['phone'=>$phone,'is_del'=>0])->find();
    	
    	if(empty($info)) {
    		$ndata = [
	    		'phone' => $phone,
	    		'create_ctime' => date('Y-m-d H:i:s')
    		];
    		
    		$wechat = $request->param('wechat');//邀请人信息
    		
    		$ndata['password'] = md5(md5(trim(substr($ndata['phone'],-6))));
    		if(!empty($wechat))
    		{
    			$inviteInfo = $agent->field('agent_id,family')->where(['phone'=>$wechat])->find();// 邀请人族谱及ID
	    		if(!empty($inviteInfo))
	    		{
	    			$ndata['inviter']    = $inviteInfo->agent_id;
	    			$ndata['family']     = $inviteInfo->family.','.$inviteInfo->agent_id;
	    			$ndata['family']     = trim($ndata['family'],',');
	    			$ndata['generation'] = count(explode(',', $ndata['family']))+1;// 计算代数
	    		}
    		}
    		$result = $agent->save($ndata);//添加注册信息
    		
    		$data['code']=4005;
    		$data['message']='注册失败，请重试';
    		
    		if($result) {
	    		$data['code']=1;
	    		$data['message']='注册成功';
    		}  
    		
    	} else {
    		$data['code']=4004;
    		$data['message']='手机号码已注册';
    	}
    	 
    	//返回结果
    	Response::create(['data'=>$data], 'jsonp')->send();
    	exit;
    }
    
    
    //登录
    public function login()
    {
    	$data=array();
    	
    	$request = request();
    	$phone = $request->param('phone');
    	$password = $request->param('password');
 
    	$agent = model('Agents');
    	$info = $agent->field('agent_id as a_id,phone,password,nickname,end_etime,name,sex,stock_money,province,city,area,inviter,generation,role,wechat,status,head_img,is_use')->where(['phone'=>$phone,'is_del'=>0])->find();
    	
    	if(empty($phone) || empty($password)) {
    		$data['code']=4009;
    		$data['message']='缺少手机号或者密码';
    	}
    	
    	if(!empty($info))
    	{
    		if($info['password'] != md5(md5(trim($password))))
    		{
    			$data['code']=4006;
    			$data['message']='密码错误，请重新登录！';
    		} else {
    	
	    		$data['code']=1;
	    		$data['data']=$info;
	    		$data['message']="登录成功";
	    		//session生成
	    		Session::set('agent_id',$info['a_id']);
    		}
    		
    	}else{
    		
    		$data['code']=4007;
    		$data['message']='无此手机号，请注册！';
    	}
    	
    	//返回结果
    	Response::create(['data'=>$data], 'jsonp')->send();
    	exit;
    }
    
    //校验登录状态
    private  function checkLogin()
    {
    	$data=array();
    	
    	if(!session('?agent_id')){
    		$data['code']=4008;
    		$data['message']='登录失败';
    	}
    	
    	if(!empty($data)) {
	    	//返回结果
	    	Response::create(['data'=>$data], 'jsonp')->send();
	    	exit;
    	}
    }
    
    //升级大礼包
    public function promotionGift()
    {
    	$this->checkLogin();
    	$data=array();
    	
    	$agent_id=session('agent_id');
    	$agent=model('Agents');
    	$info=$agent->getInfoByCondition(['agent_id'=>$agent_id,'is_del'=>0]);// 获取未删除代理商的信息
    	
    	$gift=model('Promotiongift');
		
    	
    	$giftList=$gift->where('number>0 and type>'.$info['role'])->order('type desc,id desc')->select();
    	
    	$data['code']=1;
    	$data['data']=$giftList;
    	$data['message']="数据获取成功!";
    	
    	
    	//返回结果
    	Response::create(['data'=>$data], 'jsonp')->send();
    	exit;
    }
    
    //购物车
    public function cart()
    {
    	$this->checkLogin();
    	$data=array();
    	
    	$agent_id=session('agent_id');
    	
    	$list = model('Products')->alias('p')->field('p.product_name,p.sales_price,p.product_img,p.inventory,c.id AS cid,c.pid,c.num,is_Purchase_a')->join('agent_cart c','p.id=c.pid')->where(['c.a_id'=>$agent_id])->select();
    	
    	$data['code']=1;
    	$data['data']=$list;
    	$data['message']="数据获取成功";
    	
    	//返回结果
    	Response::create(['data'=>$data], 'jsonp')->send();
    	exit;
    }
}
