<?php 
namespace app\index\controller;

use think\Controller;
 

class Test extends Controller{
	
	//用于获取openid
	public function message()
	{
		
		$agent_id=100011;
		$agents=model('admin/Agents');
		$info=$agents->field('agent_id,phone')->find($agent_id);
		
		$mdata=array();
		$mdata['first']='您的库存不足！';
		$mdata['account']=$info['phone'];
		$mdata['time']=date('Y-m-d H:i:s');
		$mdata['type']='库存不足';
		$mdata['remark']='因您的库存不足，无法享受代理收益，请及时充值库存';
		
		$oauth = controller('index/Oauth', 'controller');
		
		$weixin=model('Weixinusers');
		$weixinInfo=$weixin->where('agent_id='.$agent_id)->find();
		
		$dataInfo=message_setting(3,$mdata);//加载消息发送设置
 
		 
		if($weixinInfo['openid'] && config('web.weixin_message') && !empty($dataInfo))
		{
			$this->sendMessage($weixinInfo['openid'],$dataInfo);
		}
		
		//发送通知
		//message_notification(3,$info['agent_id'],$mdata);
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
					$tdata['expires_time']=time()+$arr['expires_in']-1000;//为了避免误差，结束时间提前1000秒
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
				$data['expires_time']=time()+$arr['expires_in']-100;//为了避免误差，结束时间提前100秒
				$data['access_token']=$arr['access_token'];
				$config->save($data);
				$res=$arr['access_token'];
			}

	
		}
	
		return $res;
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
	
	public function test()
	{
		$res=agent_reward_recommend_no_ordernumber(1402,2);
		 
		var_dump($res);
	}
}
?>