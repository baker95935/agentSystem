<?php

namespace app\admin\model;

use think\Model;

class Productgrouping extends Model
{
    //操作产品分组管理表
    protected $table = 'product_grouping';


    public function getGroupingById($data){
        return $this->where('grouping_name',$data)->value('id');//获取页面的参数，去分组表查询对应的nanm的id
    }

    public function getGroupingListForId($data){
        return $this->where('id',$data)->find();
    }
}
