<?php

namespace app\index\model;

use think\Model;

class Shop extends Model
{
    protected $table = 'shop';

    //定义和用户表的关联
	public function agent()
	{
		return $this->hasOne('Agents','agent_id','a_id');
	}

	// 与店铺商品表达关联
	public function goods()
	{
		return $this->hasMany('Shopgoods','a_id','a_id');
	}
}