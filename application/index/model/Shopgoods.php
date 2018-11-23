<?php

namespace app\index\model;

use think\Model;

class Shopgoods extends Model
{
    protected $table = 'shop_goods';

    // 定义和商品表的关联
	public function product()
	{
		return $this->hasOne('Products','id','p_id');
	}

	/**
	 * 统计数量
	 *
	 * @param int $agent_id 代理商ID
	 * @param int $type 类型:1上架 2下架
	 */
	public function getCount($agent_id,$type=1)
	{
		if(empty($agent_id) || !in_array($type,[1,2]))
		{
			return 0;
		}
		return $this->where(['a_id'=>$agent_id,'type'=>$type,'is_del'=>0])->count();
	}

	/**
	 * 关联获取商品列表
	 */
	public function getListJoinProduct($where,$order='')
	{
		empty($order) && $order = 'p.create_time';
		return $this->alias('sg')->field('sg.*,sg.id AS sid,p.*,(p.sales_volume+p.false_volume) AS sale_num')->join('product_management p','sg.p_id=p.id AND p.state=1')->where($where)->order($order)->select();
	}
}