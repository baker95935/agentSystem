<?php

namespace app\index\model;

use think\Model;
// 产品类目
class ProductCategory extends Model
{
    protected $table = 'product_category';

    /**
     * 通过parent_id查询子类目
     *
     * @param $id int 上级类目ID
     * @param $level int 当前相对级别(非固定级别)
     */
    protected function getSonCategoryByParentId($id,$level=1)
    {
        $return = [];
	    $list = $this->field('id,category_name,parent_id,category_img')->where(['parent_id'=>$id])->select();
	    foreach ($list as $key => $value)
	    {
	    	$cache = [];
			$cache['id']    = $value->id;
			$cache['name']  = $value->category_name;
			$cache['pid']   = $value->parent_id;
			$cache['img']   = $value->category_img;
			// $cache['level'] = $level;
			$return[] = $cache;
	    }
	    return $return;
    }

    /**
     * 获取产品所有级别类目
     */
    public function getCategory($id = 0)
    {
    	return $this->loopSelectSonCategory($id);// 所有级别类目
    }

    /**
     * 按级别递归查询子级类目并返回该级全部
     *
     * @param $id int 上级类目ID
     * @param &$level int 该类目的相对等级
     */
    protected function loopSelectSonCategory($id,&$level=1)
    {
    	$list = $this->getSonCategoryByParentId($id,$level);
    	foreach ($list as $key => &$value)
    	{
    		$level++;
    		$count = $this->where(['parent_id'=>$value['id']])->count();
    		if($count > 0)
    		{
    			$value['son'] = $this->loopSelectSonCategory($value['id'],$level);
    		}
    	}
    	return $list;
    }

    /**
     * 获取某级栏目的子级栏目及子级栏目是否包含自己的子级
     * @param int $pid 父级栏目ID
     * @return array
     */
    public function getGivenCateSonsByID($pid)
    {
        $return = [];
        $list = $this->field('id,category_name,parent_id,category_img')->where(['parent_id'=>$pid])->select();
        if($list)
        {
            foreach ($list as $key => $value)
            {
                $cache = [];
                $cache['id']    = $value->id;
                $cache['name']  = $value->category_name;
                $cache['pid']   = $value->parent_id;
                $cache['img']   = $value->category_img;
                $cache['hasSon'] = $this->where(['parent_id'=>$value->id])->count();
                $return[] = $cache;
            }
        }
        return $return;
    }

    /**
     * 获取某栏目的上级族谱(到顶级)栏目ID
     * @param  int $son_id 给定的子级栏目ID
     * @return array
     */
    public function getParentFamilyByID($son_id,&$return = [])
    {
        $father = $this->where(['id'=>$son_id])->column('parent_id');// 查询上级ID
        if(count($father) > 0 && $father[0] > 0)
        {
            $return[] = $father[0];// 添加上级ID
            $this->getParentFamilyByID($father[0],$return);
        }
        return $return;
    }

    /**
     * 类目路径
     * @param  string/array $ids 路径ID
     * @return array
     */
    public function categoryMapsArr($ids)
    {
        $ids = is_array($ids) ? implode(',',$ids) : $ids;
        $list = $this->field('id,category_name,parent_id,category_img')->where(['id'=>['in',$ids]])->select();
        return $list;
    }

    /**
     * 获取某级栏目的所有子级栏目的ID
     * @param  integer $cate_id 指定的某级栏目ID
     * @return array           [n1,n2,...n]
     */
    public function getAllSonsCateArrByID($cate_id = 0,&$return = [])
    {
        $list = $this->where(['parent_id'=>$cate_id])->select();
        if($list)
        {
            foreach ($list as $key => $value)
            {
                $return[] = $value->id;// 添加子级栏目ID
                $this->getAllSonsCateArrByID($value->id,$return);
            }
        }
        return $return;
    }
}