<?php

namespace app\Admin\model;

use think\Model;

class Expresscompany extends Model
{
    protected $table = 'express_company';

    /**
     * 获取所有启用的物流公司
     *
     * @param $key string 名称
     * @param $use Boolean 是否启用:true 返回所有已启用的 false 返回所有未删除的
     */
    public function getAllExpressCompany($key = '',$use = false)
    {
    	$where = ['is_del'=>0];
    	if($use)
    	{
    		$where['is_use'] = 1;
    	}
    	if($key)
    	{
    		$where['name'] = ['like','%'.$key.'%'];
    	}
    	$list = $this->where($where)->select();
    	return collection($list)->toArray();
    }

    /**
     * 检查该名称是否已存在
     */
    public function checkNameIsSet($name)
    {
    	if($name)
    	{
    		$result = $this->where(['name'=>$name,'is_del'=>0])->count();
    		return $result;
    	}else{
    		return false;
    	}
    }
}