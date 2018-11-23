<?php

namespace app\admin\model;

use think\Model;
use think\Cache;

/**
 * 级联省市区
 */
class BasicDataAddress extends Model
{
    protected $table = 'basic_data_address';

    /**
     * 获取省级列表
     */
    public function getProviceList()
    {
    	$data = $this->field('id,name,parent_id,level_type')->where('level_type',1)->order('id','asc')->select();
    	return $data;
    }

    /**
     * 根据id获取子级城市列表
     *
     * @param $id int 城市ID
     * @return Array
     */
    public function getSonCityList($id)
    {
    	if (!empty($id))
    	{
    		$level_type = $this->field('level_type')->where('id',$id)->find();
    		// 省/市级别可往下查
    		if ($level_type->level_type >= 1 && $level_type->level_type <= 2)
    		{
    			$data = $this->field('id,name')->where('parent_id',$id)->order('id','asc')->select();
    			return $data;
    		} else {
    			return array();
    		}
    	} else {
    		return array();
    	}
    }

    // 获取省级城市数组
    public function getProvinceArr()
    {
        return $data = collection($this->getProviceList())->toArray();
    }

    // 获取子级城市数组
    public function getSonCityArr($id)
    {
        return collection($this->getSonCityList($id))->toArray();
    }

    /**
     * 获取城市名称
     *
     * @param $condition string 城市id:'1,2,3,4,5'
     * @return string 城市名称字符串:'北京,天津,河北省'
     */
    public function getNamesByIds($condition)
    {
        $return = '';
        if ($condition)
        {
            if(count(explode(',', $condition)) > 1000)
            {
                $list = $this->query('SELECT `name` FROM '.$this->table.' WHERE `id` IN ('.$condition.') ');
            }else{
                $list = $this->field('name')->where(['id'=>['in',$condition]])->select();
            }
            $list   = array_map('array_values', (collection($list)->toArray()));
            $return = array_reduce($list,'array_reduction_for_city');
        }
        return $return;
    }

    /**
     * 获取城市ID
     *
     * @param $condition array 检索条件:['name'=>'','id'=>'','parent_id'=>'']
     * @param $level int 级别:1省2市3区
     * @return [type] [description]
     */
    public function searchCityId($condition,$level=1)
    {
        $where  = [];
        $result = null;
        isset($condition['name']) && $where['name'] = ['like','%'.$condition['name'].'%'];
        isset($condition['id']) && $where['id'] = $condition['id'];
        isset($condition['parent_id']) && $where['parent_id'] = $condition['parent_id'];
        if($where)
        {
            $where['level_type'] = $level;
            $result = $this->field('id,parent_id')->where($where)->find();
        }
        return $result;
    }

    /**
     * 获取所有城市列表
     * @return [type] [description]
     */
    public function getAllList()
    {
        $cache_name = 'AllBasicCityList';
        $list = Cache::get($cache_name);
        if(empty($list))
        {
            $all = $this->field('`id`,`name`,`parent_id`,`level_type`')->select();
            $all = collection($all)->toArray();
            $p = $c = $a = [];
            foreach ($all as $key => $value)
            {
                if($value['level_type'] == 1)
                {
                    $p[] = $value;
                }
                if($value['level_type'] == 2)
                {
                    $c[$value['parent_id']][] = $value;
                }
                if($value['level_type'] == 3)
                {
                    $a[$value['parent_id']][] = $value;
                }
            }
            $list = [$p,$c,$a];
            !empty($list) && Cache::set($cache_name,$list,0);
        }
        return $list;
    }
}