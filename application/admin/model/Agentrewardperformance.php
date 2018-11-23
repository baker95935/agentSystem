<?php

namespace app\admin\model;

use think\Model;

class Agentrewardperformance extends Model
{
    protected $table = 'agent_reward_performance';

    //和代理等级表做一对一关联
    public function Agentlevel()
    {
    	return $this->hasOne('Agentlevel','id','role');
    }

    // 根据角色获取系数
    public function getRatioByRole($role)
    {
    	$result = $this->where(['role'=>$role])->column('ratio');
    	return $result ? $result[0] : 0;
    }
}
