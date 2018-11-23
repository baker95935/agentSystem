<?php

namespace app\admin\model;

use think\Model;

class Weixinusers extends Model
{
	protected $table = 'weixin_users';
	
	
	//校验代理商是否绑定了微信
	public function getIsBandByAgentId($agent_id)
	{
		return $this->where('agent_id='.$agent_id)->count();
	}
}
