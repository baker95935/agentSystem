<?php

namespace app\index\model;

use think\Model;

class Agentstocktransfer extends Model
{
	protected $table = 'agent_stock_transfer';
	
	public function getList($where)
	{
		$res = array();
		$res = $this->where($where)->order('id desc')->select();
		return $res;
	}
}
