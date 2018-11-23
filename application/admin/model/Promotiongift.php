<?php

namespace app\admin\model;

use think\Model;

class Promotiongift extends Model
{
	protected $table = 'promotion_gift_info';
	
	//定义和等级表的关联
	public function level()
	{
		return $this->hasOne('Agentlevel','id','type');
	}
}
