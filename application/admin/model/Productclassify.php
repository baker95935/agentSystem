<?php

namespace app\admin\model;

use think\Model;

class Productclassify extends Model
{
    //操作产品分类管理表
    protected $table = 'product_classify';
    /**
     * 勾选一个选项后操作勾选下级项目
     */
    public function getCheckId($id){

        $data=$this->field('id')->where('parent_id',$id)->select();
       
        if(!empty($data)){
            $temp=$this->where('parent_id',$id)->column('id');
            $cdata=$this->field('id')->where('parent_id','in',$temp)->select();
            $data=array_merge($data,$cdata);
        } 
        return  json_encode($data);
    }

    /**
     * 获取产品分类下一级数据集
     */
    public function getCalssifyById($strArray){
       return $this->where('parent_id','in',$strArray)->column('id');
    }
    /**
     * 根据选择的数据集删除数据
     */
    public function deleteClassifyById($strArray){
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
    public function getClassifyByCount($strArray){
        if(count($strArray)==0){
            return 0;
        }else{
            return $this->where('parent_id','in',$strArray)->count();
        }
       
    }

    public function getClassifyByIdCount($strArray){
        if(count($strArray)==0){
            return 0;
        }else{
            return $this->where('id','in',$strArray)->count();
        }
    }

    /**
     * 列表查询数据集
     * @param  int  $type  分类
     * @param $data
     */
     public function getClassifyByIdPage($data,$type){
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

}
