<?php

namespace app\admin\model;

use think\Model;

class RelevanceLabel extends Model
{
    //操作产品关联的标签表
    protected $table = 'relevance_label';


    public function getRelevanceLabelForPid($data){
        return $this->where('product_label_id',$data)->distinct(true)->field('product_id')->select();
    }
    //获取关联标签表对应产品id的标签的id
    public function getRelevancelLabelListForID($data){
        return $this->where('product_id', $data)->select();
    }
}
