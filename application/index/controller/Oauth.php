<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Session;


class Oauth extends Controller
{

    //页面授权，获取用户的code 第一步
    public function getWechatCode()
    {
    	$received = request();
    	$phone = $received->param('phone','');// 邀请人手机号
        $bind  = $received->param('bind',false);// 是否绑定请求来源
        $redirect_uri=urlencode(config('web.domain')."index/Oauth/getWechatAccessToken/phone/".$phone);
        if($bind)
        {
            $redirect_uri = urlencode(config('web.domain')."index/Oauth/getWechatAccessToken/bind/".$bind);
        }
    	$url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".config('wechat.appid')."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
    	$this->redirect($url);
    }

    //网页授权，获取用户的openId 第二步
    public function getWechatAccessToken()
    {
    	$request=request();

    	$code=$request->param('code');
    	$state=$request->param('state');
    	$phone = $request->param('phone','');// 邀请人手机号
        $bind  = $request->param('bind',false);// 是否返回绑定操作来源
    	if($code && $state=='STATE')
    	{
    		$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".config('wechat.appid')."&secret=".config('wechat.secret')."&code=".$code."&grant_type=authorization_code";
    		$response = file_get_contents($url);
    		$arr = json_decode($response, true);

    		if(!empty($arr)) {
    			$accessToken=$arr['access_token'];
    			$openId=$arr['openid'];
    			$this->getWechatUserInfo($accessToken,$openId,$bind);
    		}

    	}
    	Session::set('openid',$arr['openid']);
        if($bind)
        {
            $this->redirect('Personalinfo/WechatID');
        }
    	$this->redirect('Index/Index/register',['phone'=>$phone,'openid'=>$openId]);

    }

    //网页授权，获取微信用户信息
    public function getWechatUserInfo($accessToken,$openId,$agent_id='')
    {
    	$url="https://api.weixin.qq.com/sns/userinfo?access_token=".$accessToken."&openid=".$openId."&lang=zh_CN";
    	$response = file_get_contents($url);
    	$arr = json_decode($response, true);
    	if(!empty($arr)) {
    		$data=array();
    		$data['openid']=$arr['openid'];
    		$data['nickname']=$arr['nickname'];
    		$data['headimgurl']=$arr['headimgurl'];
    		$data['city']=$arr['city'];
    		$data['province']=$arr['province'];
    		$data['sex']=$arr['sex'];
    		$data['create_time']=time();
            if($agent_id)
            {
                $data['agent_id'] = $agent_id;
            }

    		//校验下openid是否存在，如果不存在那么注册
    		$users=model('Weixinusers');
    		$count=$users->where("openid='".$data['openid']."'")->count();
    		if($count==0) {
    			$users->save($data);
    		}else if($count == 1 && $agent_id){
                $users->where(['openid'=>$arr['openid']])->update(['agent_id'=>$agent_id]);
            }
    	}
    }

    //消息发送
    public function sendMessage($openid,$dataInfo)
    {
    	//获取acc_token
    	$access_token=$this->getAccessToken();
    	$template_id=$dataInfo['template_id'];
    	$url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
    	$data=array();
    	$data['touser']=$openid;
    	$data['template_id']=$template_id;
    	$data['url']='';

    	$data['data']= $dataInfo['data'];

    	$res = $this->request_post($url, json_encode($data));

    }

    public function test()
    {
    	$request=request();

    	$openid=Session::get('openid');
    	empty($openid) && $openid=$request->param('openid');

    	if(config('web.online')==1 && empty($openid)) {

	    	$this->redirect('Index/Oauth/silentIndex',['phone'=>'13146794572']);// (CYl 249服务器测试需要临时关闭)
	    	$openid=Session::get('openid');
	    	empty($openid) && $openid=$request->param('openid');
    	}
    	var_dump($openid);exit;
    }

    //post请求模拟
    function request_post($url = '', $param = '')
    {
    	if (empty($url) || empty($param)) {
    		return false;
    	}

    	$postUrl = $url;
    	$curlPost = $param;
    	$ch = curl_init();//初始化curl
    	curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    	curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    	curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    	$data = curl_exec($ch);//运行curl
    	curl_close($ch);

    	return $data;
    }


    //静默方式获取用户的OPENID-开始
    public function silentIndex()
    {
        $received = request();
        $phone = $received->param('phone');// 邀请人手机号
    	$appid=config('wechat.appid');
    	$redirect_uri=urlencode(config('web.domain')."index/Oauth/getUserOpenIdSilent/phone/".$phone);
    	$url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
    	//header("Location:$url");
    	$this->redirect($url);
    	//echo "<a href='".$url."'>点击";
    }


    //静默方式获取用户的OPENID-第二步
    public function getUserOpenIdSilent()
    {
    	$request=request();

    	$code=$request->param('code');
    	$state=$request->param('state');
        $phone = $request->param('phone');// 邀请人手机号

    	if($code && $state=='STATE')
    	{
    		$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".config('wechat.appid')."&secret=".config('wechat.secret')."&code=".$code."&grant_type=authorization_code";
    		$response = file_get_contents($url);
    		$arr = json_decode($response, true);

    		if(!empty($arr)) {

    			$accessToken=$arr['access_token'];
    			$openid=$arr['openid'];
    			//校验openid是否已注册
    			$user=model('Weixinusers');
    			$count=$user->where("openid='".$openid."'")->count();
    			if($count==0) {
	    			//把openid插入库中，方便辨识
	    			$data=array();
	    			$data['openid']=$arr['openid'];
	    			$data['create_time']=time();
	    			$data['scope']=$arr['scope'];
	    			$user->save($data);
    			}
    			Session::set('openid',$arr['openid']);
    			$this->redirect('Index/Index/register',['phone'=>$phone,'openid'=>$openid]);

    		}

    	}
    }

    //基础access_token获取
    public function getAccessToken()
    {
    	$res='';

    	//根据appid和secret读取库中是否有效，有效就用，没有就发起请求
    	$appid=config('wechat.appid');
    	$secret=config('wechat.secret');

    	$config=model('index/Weixinconfig');
    	$time=time();
    	$result=$config->where(array('appid'=>$appid,'secret'=>$secret))->field('expires_time,access_token')->find();

    	//如果有数据
    	if(!empty($result)) {
	    	if($result['expires_time']>time()) {
	    		$res=$result['access_token'];

	    	} else {//如果条件不符合，那么更新下
		    	$token_url ="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
		    	$response = file_get_contents($token_url);
		    	$arr = json_decode($response, true);
		    	if(!empty($arr)) {
			    	$tdata['expires_time']=time()+$arr['expires_in']-7000;//为了避免误差，结束时间提前3000秒
			    	$tdata['access_token']=$arr['access_token'];
			    	$config->save($tdata,array('appid'=>$appid,'secret'=>$secret));
		    	}

		    	$res=$arr['access_token'];
	    	}
    	} else {//如果第一次执行，那么插入到库

    		$token_url ="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
    		$response = file_get_contents($token_url);
    		$arr = json_decode($response, true);
    		if(!empty($arr)) {
    			$data=array();
    			$data['appid']=$appid;
    			$data['secret']=$secret;
    			$data['create_time']=time();
    			$data['expires_time']=time()+$arr['expires_in']-7000;//为了避免误差，结束时间提前3000秒
    			$data['access_token']=$arr['access_token'];
    			$config->save($data);
    			$res=$arr['access_token'];
    		}

    	}

    	return $res;
    }

    //获取推广二维码的票
    public function getQRcodeTicket()
    {
    	//获取acc_token
    	$access_token=$this->getAccessToken();
    	$url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;

    	$data=array();
    	$data['action_name']='QR_LIMIT_STR_SCENE';
    	$data['action_info']['scene']['scene_str']='index';

    	$res = $this->request_post($url, json_encode($data));
    	$result=json_decode($res);

    	$imgurl="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($result->ticket);
    	$response = file_get_contents($imgurl);


    }

}
