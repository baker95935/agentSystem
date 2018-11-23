<?php

namespace app\index\model;

use think\Model;

// 产品的多图资料
class ProductImagesData extends Model
{
    protected $table = 'product_many_img';

    /**
     * 通过ID获取产品的多图资料
     *
     * @param $pid int 产品ID
     */
    public function getProductImgsById($pid)
    {
    	return $this->field('id,name,size,img,time')->where(['product_id'=>$pid])->select();
    }
    
    //获取多套的数量
    public function getProductImgsCountById($pid)
    {
    	return $this->where('product_id='.$pid)->count();
    }
}