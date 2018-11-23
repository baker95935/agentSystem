<?php

namespace app\admin\model;

use think\Model;

class Agentlevel extends Model
{
    protected $table = 'agent_level';

    /**
     * 通过id获取角色名
     */
    public function getNameById($id)
    {
        if ($id == 0)
        {
            $result = ['会员'];
        } else {
            $result = $this->where(['show'=>1,'valid'=>1,'id'=>$id])->column('name');
        }
    	return $result ? $result[0] : '';
    }

    /**
     * 获取所有角色名(前后台共用)
     *
     * @param $type int 区分前后台:前台不显示隐藏的,后台显示隐藏的
     */
    public function getRoleName($type = 1)
    {
        $where['valid'] = 1;
        if($type != 1)
        {
            $where['show'] = 1;
        }
    	$result = $this->where($where)->select();
    	$data = ['会员','','','','','',''];// 默认值
    	if($result)
    	{
    		foreach ($result as $key => $val)
    		{
    			$cache = $val->data;
    			$data[$val->id] = $cache['name'];
    		}
    	}
    	return $data;
    }

    /**
     *
     * @return 获取所有角色列表
     */
    public function gerRoleList()
    {
    	$res=array();

    	$dict=model('admin/Basicnamedictionary');
		$first=$dict->where("value='outsider'")->value('name');
		$res[0]['id']=0;
		$res[0]['name']=$first;

		$levelList=$this->where(['show'=>1,'valid'=>1])->field('id,name')->select();

		foreach($levelList as $k=>$v){
			$res[]=$v;
		}

		return $res;

    }

    //获取最低等级的等级信息
    public function getLastRoleInfo()
    {
    	return $this->where(['show'=>1,'valid'=>1])->order('id asc')->find();
    }
}
