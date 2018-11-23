<?php

namespace app\admin\model;

use think\Model;

class Agentrewardrecommend extends Model
{
    protected $table = 'agent_reward_recommend';
    
    //和代理等级表做一对一关联
    public function Agentlevel()
    {
    	return $this->hasOne('Agentlevel','id','role');
    }
}
