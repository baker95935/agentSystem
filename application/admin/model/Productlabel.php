<?php

namespace app\admin\model;

use think\Model;

class productlabel extends Model
{
    //操作产品标签表
    protected $table = 'product_label';


    public function getProductLabelForId($data){
       return $this->where('id',$data)->find();
    }

    public function getProductLabelByIdForName($data){
        return $this->where('product_name',$data)->value('id');
    }
}
