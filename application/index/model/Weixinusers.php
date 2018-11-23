<?php

namespace app\index\model;

use think\Model;

class Weixinusers extends Model
{
	protected $table = 'weixin_users';
	
	
	//获取用户的openid
	public function getUserOpenid($agent_id)
	{
		$res='';
		$res=$this->where('agent_id='.$agent_id)->value('openid');
		return $res;
	}
	
	//获取代理商ID
	public function getAgentIdByOpenId($openid)
	{
		$res='';
		$res=$this->where("openid='".$openid."'")->value('agent_id');
		return $res;
	}
}
