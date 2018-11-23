<?php

namespace app\index\model;

use think\Model;

class Products extends Model
{
    protected $table = 'product_management';

    /**
     * 查询上架产品信息
     *
     * @param $pid int 产品ID
     * @param $field string 查询字段的字符串
     * @return obj
     */
    public function getOnsaleProductInfoById($pid,$field='')
    {
    	$field = empty($field) ? 'id,product_name,category_id,classify_id,product_img,explain,sales_price,cost_price,unit,weight,inventory,sales_volume,details,specification,state,exemption_from_postage,is_gift,is_first_order,create_time,false_volume,(sales_volume+false_volume) as mix_volume,is_Purchase_a,is_agent_level': $field;

    	return $this->field($field)->where(['id'=>$pid,'state'=>1])->find();
    }

    
    //校验首单产品的，购买次数
    public function getFirstOrderProductCountById($pid,$agent_id)
    {
    	$res=0;
    	$info=$this->field('is_first_order')->find($pid);
 
    	if($info['is_first_order']==1) {
    		$data=array();
    		$data['pid']=['in',$pid];
    		$data['agent_id']=$agent_id;
    		$data['order_status']=['neq','7'];
    		$count=model('Orders')->where($data)->count();
    		$count>0 && $res=1;
    	}
    	
    	return $res;
    }
    
    //获取产品的 是否首单产品
    public function getFirstOrderAttrById($pid)
    {
    	return $this->where('id='.$pid)->value('is_first_order');
    }
    
    //校验限购产品的，购买次数
    public function getLimitProductCountById($pid,$agent_id)
    {
    	$res=0;
    	$info=$this->field('is_Purchase_a')->find($pid);
    
    	if($info['is_Purchase_a']==1) {
    		$data=array();
    		$data['pid']=['in',$pid];
    		$data['agent_id']=$agent_id;
    		$data['order_status']=['not in',['6','7']];
    		$count=model('Orders')->where($data)->count();
    		$count>0 && $res=1;
    	}
    	 
    	return $res;
    }
}
