<?php

namespace app\admin\model;

use think\Model;

class Productcategory extends Model
{
    //操作产品类目管理表
    protected $table = 'product_category';
    /**
     * 获取所有勾选的ID即所有下级ID
     */
    public function getCheckId($id){  

        $thirdData=$fourData=[];    
        //找第二级勾选的
        $data=$this->field('id')->where('parent_id',$id)->select();
        if(!empty($data)){
            $secondData=$this->where('parent_id',$id)->column('id');
        }
        //找第三级勾选的
        $data=$this->field('id')->where('parent_id','in',$secondData)->select();
        if(!empty($data)){
            $thirdData=$this->getCategoryById($secondData);
        }
        //找第四级勾选的
        $data=$this->field('id')->where('parent_id','in',$thirdData)->select();
        if(!empty($thirdData)){
            $fourData=$this->getCategoryById($thirdData);
        }
        $data=array_merge($fourData,array_merge($secondData,$thirdData));
        return  json_encode($data);
    }
   /**
     * 获取产品类目下一级数据集
     */
    public function getCategoryById($strArray){
        return $this->where('parent_id','in',$strArray)->column('id');
     }
     /**
      * 根据选择的数据集删除数据
      */
     public function deleteCategoryById($strArray){
         if(count($strArray)==0){
             return 1;
         }else{
             return $this->where('id','in',$strArray)->delete();
         }
        
     }
     /**
      * 根据数据集查询下级数量
      *
     */
     public function getCategoryByCount($strArray){
         if(count($strArray)==0){
             return 0;
         }else{
             return $this->where('parent_id','in',$strArray)->count();
         }
        
     }

    /**
     * 查询数据集中的数量
     */
     public function getCategoryByIdCount($strArray){
        if(count($strArray)==0){
            return 0;
        }else{
            return $this->where('id','in',$strArray)->count();
        }
    }
    //获取产品类目开始
    public function getCategoryList(){
            
        $categoryList=$this->where('parent_id', '0')->order('id', 'desc')->select();
        foreach ($categoryList as $k=>$v){
            $categoryList['id'] = $v['id'];
        }

        return $categoryList;
    }
    /**
     * 列表查询数据集
     * @param  int  $type  分类
     * @param $data
     */
    public function getCategoryByIdPage($data,$type){

        $returnValue=null;
        switch($type){
            case 1://显示全部
            $count=$this->where('parent_id','0')->count();
            $returnValue=$this->where('parent_id','0')->order('id', 'desc')->paginate(config('paginate.list_rows'),$count);
            break;
            case 2://代理查询条件
            $count=$this->where($data)->count();
            $returnValue= $this->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'),$count);
            break;
            case 3://获取ID值
            $returnValue=$this->where($data)->column('id');
            break;
            case 4://根据数据集查询子类并按ID倒序排序
            $returnValue=$this->where('parent_id', $data)->order('id', 'desc')->select();
            break;
            case 5://获取parent_id
            $returnValue=$this->field('parent_id')->where('id',$data)->find();
            break;
            case 6://获取制定ID的所有数据
            $returnValue=$this->where('id',$data)->find();
            break;
            case 7:
            $count=$this->where('id','in',$data)->count();
            $returnValue=$this->where('id','in',$data)->order('id','desc')->paginate(config('paginate.list_rows'),$count);
            break;
            case 8:
            $returnValue=$this->where('id','in',$data)->select();
            break;
        }

        return $returnValue;
       
    }
    
    
    /**
     *根据ID,获取所有子ID串 
     * @param int $id
     */
    public function getAllSubCategoryStrByID($id)
    {
    	$res=array();
    	
    	//初始化
    	$res[]=$id;
    	
    	$tmp=$this->getCategoryById($id);
        
    	//假设顶级，最多3次
    	if(!empty($tmp)) {
    		$res=array_merge($res,$tmp);
    	 
    		//第二次
    		if(!empty($tmp)) {
    			$tmpSecond=$this->getCategoryById($tmp);
    			if(!empty($tmpSecond)) {
    				$res=array_merge($res,$tmpSecond);
    				
    				//第三次
    				if(!empty($tmpSecond)) {
    					$tmpThree=$this->getCategoryById($tmpSecond);
    					!empty($tmpThree) && $res=array_merge($res,$tmpThree);
    				}
    			}
    		}
    		
    	}
        	return $res;
    		 
    }

    public function getTopLevelInfo($cid)
    {
        $res=array();
        $category=model('Productcategory');

        //获取当前类目信息
        $categoryInfo=$category->find($cid);

        $res[0]=$cid;

        //寻找上级
        if($categoryInfo['parent_id'])
        {
            $res[0]=$categoryInfo['parent_id'];
            $res[1]=$cid;

            $oneLevelInfo=$category->find($categoryInfo['parent_id']);
            //寻找第三级
            if($oneLevelInfo['parent_id'])
            {
                $res[0]=$oneLevelInfo['parent_id'];
                $res[1]=$categoryInfo['parent_id'];
                $res[2]=$cid;

                $towLevelInfo=$category->find($oneLevelInfo['parent_id']);

                //寻找第四级
                if($towLevelInfo['parent_id']){
                    $res[0]=$towLevelInfo['parent_id'];
                    $res[1]=$oneLevelInfo['parent_id'];
                    $res[2]=$categoryInfo['parent_id'];
                    $res[3]=$cid;

                }

            }
        }

        return $res;

    }
    /**
     * 根据id获取子级类目列表
     *
     * @param $id int 类目ID
     * @return Array
     */
    public function getCategoryoList($id)
    {
        if (!empty($id))
        {
            $level_type = $this->field('level_type')->where('id',$id)->find();

            // 一级 /二级三级别可往下查
            if ($level_type->level_type >= 1 && $level_type->level_type <= 3  )
            {
                $data = $this->field('id,category_name')->where('parent_id',$id)->order('id','asc')->select();

                return $data;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }
    public function getProductcategory($id){
        $cpid=array();
        $temp=$this->getCategoryByIdPage($id,5);

        if($temp['parent_id']){
            $cpid[]=$temp['parent_id'];
            $temp=$this->getCategoryByIdPage($temp['parent_id'],5);
            if($temp['parent_id']){
                $cpid[]=$temp['parent_id'];
                $temp=$this->getCategoryByIdPage($temp['parent_id'],5);
                if($temp['parent_id']){
                    $cpid[]=$temp['parent_id'];
                    $temp=$this->getCategoryByIdPage($temp['parent_id'],5);
                    if($temp['parent_id']){
                        $cpid[]=$temp['parent_id'];
                    }

                }

            }

        }

        return $cpid;
    }

}
