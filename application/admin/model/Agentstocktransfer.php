<?php

namespace app\admin\model;

use think\Model;

class Agentstocktransfer extends Model
{
	protected $table = 'agent_stock_transfer';
	
	//定义和角色等级表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
}
