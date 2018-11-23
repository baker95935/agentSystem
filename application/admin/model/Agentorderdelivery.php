<?php

namespace app\admin\model;

use think\Model;

class Agentorderdelivery extends Model
{
	protected $table = 'agent_order_delivery';
	

	//定义公开属性 可调用
	public $expressName=array(
			'ems'=>'EMS',
			'shunfeng'=>'顺丰',
			'shentong'=>'申通',
			'yuantong'=>'圆通',
			'zhongtong'=>'中通',
			'huitongkuaidi'=>'百世汇通',
			'baishiwuliu'=>'百世物流',
			'yunda'=>'韵达',
			'zhaijisong'=>'宅急送',
			'tiantian'=>'天天',
	);
	
}
