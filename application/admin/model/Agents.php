<?php

namespace app\admin\model;

use think\Model;

class Agents extends Model
{
    protected $table = 'agents';

    // 性别
    public function getSexAttr($value)
    {
        $sex = ['w'=>'女','m'=>'男'];
        if(in_array($value, ['m','w']))
        {
            return $sex[$value];
        }else{
            return $sex['m'];
        }
    }

    /**
     * 获取代理商表中最高代数
     *
     * @return Int
     */
    public function getLastGeneration()
    {
    	$generation = $this->max('generation');
    	$generation = ($generation >= 1) ? $generation : 0;// 默认最小1代
    	return $generation+1;// 根据表中最高代数,可选代数为最高代+1
    }

    /**
     * 获取邀请人(代理商)用于手动添加功能
     *
     * @param array $condition 条件:0键关键词 1键当前代理商ID
     * @return Array
     */
    public function getAllAgents($condition)
    {
    	$return = [];
    	$where = ['is_del'=>0];
    	if($condition && !empty($condition[0]))
    	{
			$where['nickname|phone|name|agent_id'] = ['like','%'.$condition[0].'%'];
    	}
    	if($condition && !empty($condition[1]))
    	{
			// 邀请级别限制:不能查询当前用户邀请的成员和自己
			$down_id = $this->where(['family'=>['like','%'.$condition[1].'%']])->column('agent_id');
			if (!empty($down_id))
			{
				$down_id[] = $condition[1];
				$where['agent_id'] = ['not in',implode(',', $down_id)];
			}else{
				$where['agent_id'] = ['<>',$condition[1]];
			}
    	}
    	$agents = $this->field('nickname,name,agent_id,generation')->where($where)->select();
    	if ($agents)
    	{
    		foreach ($agents as $key => $val)
    		{
    			$cache = [];
				$cache['a_id']     = $val->data['agent_id'];
				$cache['name']     = $val->data['name'];
				$cache['nickname'] = $val->data['nickname'];
                $cache['generation'] = $val->data['generation'];
				$return[] = $cache;
    		}
    	}
        array_multisort($return,SORT_DESC);
    	return $return;
    }

    /**
     * 获取某一代理商所有子级族谱的代理商信息
     *
     * @param $condition array 条件[$agent_id[,$fields]]
     * @return Array
     */
    public function getAllSonAgentList($condition)
    {
        $return = [];
        $fields = (isset($condition[1]) && !empty($condition[1])) ? trim($condition[1],','): 'agent_id,family,inviter,generation';
        $agents = $this->field($fields)->where(['family'=>['like','%'.$condition[0].'%']])->select();
        if($agents)
        {
            foreach ($agents as $key => $val)
            {
                $cache = [];
                $cache['a_id']       = $val->data['agent_id'];
                $cache['family']     = $val->data['family'];
                $cache['inviter']    = $val->data['inviter'];
                $cache['generation'] = $val->data['generation'];
                $return[] = $cache;
            }
        }
        return $return;
    }

    /**
     * 返回当前要修改的代理商的新族谱值(family)
     *
     * @param $condition array [邀请代理商ID]
     * @return string
     */
    public function getNewFamily($condition)
    {
        $target_agent_family = $this->where(['agent_id'=>$condition[0],'is_del'=>0])->column('family');
        return trim($target_agent_family[0].','.$condition[0],',');
    }

    /**
     * 循环修改目标代理商所有子级代理商的族谱(family)
     *
     * @param $condition array [目标代理商ID]
     */
    public function loopModifySonsFamily($condition)
    {
        $son_list = $this->getAllSonAgentList([$condition[0]]);
        $info = $this->where(['agent_id'=>$condition[0]])->column('family');
        if(count($son_list) > 0)
        {
            foreach ($son_list as $son_list_k => $son_list_v)
            {
                $son_list_family = trim($info[0].','.substr($son_list_v['family'], strpos($son_list_v['family'], $condition[0])),',');
                $this->save(['family'=>$son_list_family],['agent_id'=>$son_list_v['a_id']]);
            }
        }
    }

    //定义和角色等级表的关联
	public function roles()
	{
		return $this->hasOne('Agentlevel','id','role');
    }

    /**
     * 获取代理商信息
     */
    public function getAgents($agentId){
        return $this->where('agent_id',$agentId)->find();
    }

    //获取代理商的邀请人
    public function getAgentsInviter($aid)
    {
    	$res=0;
    	$res=$this->where('agent_id='.$aid)->value('inviter');
    	return $res;
    }
}