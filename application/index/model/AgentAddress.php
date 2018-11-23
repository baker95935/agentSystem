<?php

namespace app\index\model;

use think\Model;

class AgentAddress extends Model
{
    protected $table = 'agent_address';

    /**
     * 获取用户默认地址
     *
     * @param $agent_id int 代理商ID
     * @return Array||FALSE(无地址的返回FALSE;无默认地址的返回第一条地址信息)
     */
    public function getDefault($agent_id)
    {
    	$is_setDefault = $this->where(['a_id'=>$agent_id,'is_default'=>1,'is_del'=>0])->count();
    	if ($is_setDefault)
    	{
    		$result = $this->field('id,name,phone,province,city,area,address')->where(['a_id'=>$agent_id,'is_default'=>1,'is_del'=>0])->find();
    	} else {
    		$is_seAddress = $this->where(['a_id'=>$agent_id,'is_default'=>0,'is_del'=>0])->count();
    		if ($is_seAddress)
    		{
    			$result = $this->field('id,name,phone,province,city,area,address')->where(['a_id'=>$agent_id,'is_default'=>0,'is_del'=>0])->order('id ASC')->find();
    		} else {
    			$result = FALSE;
    		}
    	}
    	return $result;
    }
}
