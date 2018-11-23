<?php

namespace app\admin\model;

use think\Model;

class Relevancegrouping extends Model
{
    //操作产品关联表
    protected $table = 'relevance_grouping';



    public function getProductIdForId ($data){
       return $this->where('product_grouping_id',$data)->distinct(true)->field('product_id')->select();
    }
    //获取关联分组表对应产品id的分组id
    public function getRelevanceGroupingForId($data){
        return $this->where('product_id',$data)->select();
    }
}
