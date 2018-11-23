<?php

namespace app\index\model;

use think\Model;

class BasicDataAddress extends Model
{
    protected $table = 'basic_data_address';

    /**
     * 根据ID获取城市名称
     */
    public function getCityNameById($id)
    {
    	$name = $this->where(['id'=>$id])->column('name');
    	return $name[0];
    }

    /**
     * 根据城市名称获取ID
     */
    public function getCityIdByName($name)
    {
    	$id = $this->where(['name'=>$name])->column('id');
    	return $id[0];
    }
}
