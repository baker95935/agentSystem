<?php

namespace app\admin\model;

use think\Model;

class AgentApplications extends Model
{
    protected $table = 'agent_applications';

    // 定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','a_id');
	}

	/**
	 * 获取最后一次注册申请通过时间
	 * @param  integer $a_id 代理商ID
	 * @return string        时间字符串
	 */
	public function getLastRegistePassExamineTime($a_id)
	{
		return $this->where(['a_id'=>$a_id,'type'=>1,'status'=>1,'is_del'=>0])->order('examine_etime DESC')->value('examine_etime');
	}


	//获取用户升级的渠道
	public function getApplyType($a_id)
	{
		$num=0;

		$num=$this->where(['a_id'=>$a_id,'status'=>1,'is_del'=>0])->value('type');

		return $num;
	}

	/**
	 * 添加变更记录:邀请人/代数变更
	 */
	public function recordChangeRelationLog($data)
	{
		$data['type']   = 3;// 关系变更
		$data['status'] = 1;// 通过
		$data['create_ctime'] = $data['examine_etime'] = date('Y-m-d H:i:s');
		$this->insert($data);
	}
}