<?php

namespace app\admin\model;

use think\Model;

class Agentstockchange extends Model
{
	protected $table = 'agent_stock_change';
	
	//定义公开属性 可调用
	public $status=array(
		1=>'待审核',
		2=>'已审核',
		3=>'驳回',
	);
	
	//状态的属性值
	public function getStatusAttr($value)
	{
		$status = [1=>'待审核',2=>'已审核',3=>'驳回'];
		return $status[$value];
	}
	
	//充值类型
	public function getAccountTypeAttr($value)
	{
		$status = [1=>'微信',2=>'支付宝',3=>'银行卡',4=>'后台充值',5=>'其他'];
		return $status[$value];
	}
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
}
