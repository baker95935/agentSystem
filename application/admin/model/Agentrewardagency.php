<?php

namespace app\admin\model;

use think\Model;

class Agentrewardagency extends Model
{
    protected $table = 'agent_reward_agency';
    
    //和代理等级表做一对一关联
    public function Agentlevel()
    {
    	return $this->hasOne('Agentlevel','id','role');
    }
    

    //获取本模型的信息
    public function getLevelInfo($role)
    {
    	return $this->where('role='.$role)->find();
    }
}
