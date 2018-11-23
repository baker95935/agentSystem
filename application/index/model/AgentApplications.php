<?php

namespace app\index\model;

use think\Model;

class AgentApplications extends Model
{
    protected $table = 'agent_applications';

    /**
     * 获取代理商申请记录
     * @param  array $condition 查询条件
     * @return array
     */
    public function getApplies($condition)
    {
    	$return = [];
    	$arr = $this->field('id,type,create_ctime,target,status,examine_etime,remarks,examine_remark')->where(['is_del'=>0])->where($condition)->order('id DESC')->select();
    	if($arr)
    	{
    		foreach ($arr as $key => $val)
    		{
	    		$cache = [];
				$cache['id']      = $val['id'];
				if($val['type'] == 1)
				{
					$cache['typename'] = '代理商申请';
				}elseif($val['type'] == 2){
					$cache['typename'] = '升级申请';
				}else{
					$cache['typename'] = '其他';
				}
                $cache['create']  = $val['create_ctime'];
                $cache['type']    = $val['type'];
                $cache['target']  = $val['target'];
                $cache['status']  = $val['status'];
                $cache['examine'] = $val['examine_etime'];
				$cache['remarks'] = $val['remarks'];
				$return[] = $cache;
    		}
    	}
    	return $return;
    }

    /**
     * 获取代理商申请记录
     * @param  array $condition 查询条件
     * @return array
     */
    public function getAppliesWithInfo($condition)
    {
        $return = [];
        $arr = $this->alias('p')->field('p.id,p.a_id,p.target,p.status,p.examine_etime,p.create_ctime,p.examine_remark,a.name,a.phone')->join('agents a','a.agent_id=p.a_id')->where(['p.is_del'=>0])->where($condition)->order('p.id DESC')->select();
        if($arr)
        {
            $rolename = model('admin/Agentlevel')->getRoleName(0);
            foreach ($arr as $key => $val)
            {
                $cache = [];
                $cache['id']      = $val['a_id'];
                $cache['target']  = $rolename[$val['target']];
                $cache['status']  = $val['status'];
                $cache['examine'] = $val['examine_etime'];
                $cache['create']  = $val['create_ctime'];
                $cache['remarks'] = $val['examine_remark'];
                $cache['name']    = $val['name'];
                $cache['phone']   = $val['phone'];
                $return[] = $cache;
            }
        }
        return $return;
    }

}