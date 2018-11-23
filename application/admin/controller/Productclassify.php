<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Model;

class Productclassify extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $request = request();
        $name=$request->param('name');
        $classifyList=array();
        if(!empty($name)){
            $data['classify_name']=['like','%'.$name.'%'];//加搜索条件
            $classifyList=$this->sreachList($data);     
        }else{
            $data['parent_id']=0;
            $classifyList=$this->initList($data);    
        }
        
         $this->assign('classifyList',$classifyList);
         return $this->fetch();
    }
     /**
     * 展示全部列表
     */
   public function initList($data){
    $classify = model('Productclassify');
    $classifyList=$classify->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'));
    foreach ($classifyList as $k=>&$v){
        $v['sendList']=$classify->where('parent_id',$v['id'])->order('id', 'desc')->select();
        foreach ($v['sendList'] as $kk=>$vv){
            $vv['classifyThree']=$classify->where('parent_id', $vv['id'])->order('id', 'desc')->select();
        }
    }
    return $classifyList;
   }
/**
    * 根据查询条件显示
    */
    public function sreachList($data){
        
        $classify = model('Productclassify');
        $classifyList=$classify->getClassifyByIdPage($data,2);
        $Farray=$Sarray=$Tarray=array();
        foreach($classifyList as $k=>$v)
        {
            if($v['parent_id']>0){
                $t=$classify->getClassifyByIdPage($v['parent_id'],6);
                if($t['parent_id']>0){
                    $tt=$classify->getClassifyByIdPage($t['parent_id'],6);
                    $Farray[]=$tt['id'];
                    $Sarray[]=$t['id'];
                    $Tarray[]=$v['id'];
                }else{  
                    $Farray[]=$t['id'];
                    $Sarray[]=$v['id'];
                }
            }else{
                $Farray[]=$v['id'];
            }
        }
       
        $Farray=array_values(array_unique($Farray));
        $Sarray=array_values(array_unique($Sarray));
        $Tarray=array_values(array_unique($Tarray));
        
        $classifyList=$classify->getClassifyByIdPage($Farray,7);
        foreach($classifyList as $k=>$v){
           foreach($Sarray as $s=>$sv){
               $pid=$classify->getClassifyByIdPage($sv,5);
               if($v['id']==$pid['parent_id']){
                     $v['sendList']=$classify->getClassifyByIdPage($v['id'],4);
               }
           }
           if(isset($v['sendList'])){
                foreach ($v['sendList'] as $kk => $vv) {
                    foreach ($Tarray as $t => $tv) {
                        $pid=$classify->getClassifyByIdPage($tv,5);
                        if($vv['id']==$pid['parent_id']){
                            $vv['classifyThree']=$classify->getClassifyByIdPage($vv['id'],4);
                        }
                    }
                    if(!isset($vv['classifyThree'])){
                        $vv['classifyThree']=[]; 
                    }
                }
            }
            else{
                $v['sendList']=[];
            }
          
        }
        return $classifyList;
       }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {

        $classify = model('Productclassify');
        $request = request();
        $id=$request->param('id');

        //获取一级和二级分类
        $classifyList=$classify->where('parent_id', '0')->order('id', 'desc')->select();
        foreach($classifyList as $k=>&$v)
        {
            $v['classifySecond']=$classify->where('parent_id', $v['id'])->order('id', 'desc')->select();
        }
        $this->assign('classifyList',$classifyList);

        $data=array();

        !empty($id) && $data=$classify::get($id);

        $this->assign('data',$data);
        return $this->fetch();
        //return  $data;
    }
    /**
     *初始化编辑数据
     * @param  int  $id
     * @return \think\Response
     */
    public function initClass(Request $request){
        $id=$request->param('id');
        $classify = model('Productclassify');
        $data=$classify::get(['id'=>$id]);
        return json_encode($data);
    }
    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {

         $classify = model('Productclassify');
         $data=array(
            'classify_name'=>$request->param('name'),
            'id'=>$request->param('id'),
            'parent_id'=>$request->param('parentid'),
            'son_id'=>0
        );
        $result=0;
        $className=$classify::get(['classify_name'=>$data['classify_name']]);
        $parentId=$classify::get(['parent_id'=>$data['parent_id']]);
        
            if(empty($data['id'])){//添加
                if(empty($className)){
                    $data['create_time']=time();
                    $result=$classify->save($data);
                }else{
                    $code=-1;
                    $msg='产品类名重复，请重新输入！';
                }
            } else {
                $result=$classify->save($data,array('id'=>$data['id']));//更新
            }
            if($result){
                $code=1;
                $msg='成功';
            }else{
                $code=0;
                $msg='失败';
            }
        
        return  json_encode(['code'=>$code,'msg'=>$msg]);
       
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $pClass=model('Productclassify');
        $id=$request->param('id');
        $msg="删除失败";
        $returnValue=0;
        $code=-1;
        $msg=null;

        //字符串转换成数组
        $strAry = array_filter(explode(",",$id));
        $strCount=count($strAry);
      
        if($strCount==1){  
            $pCount=$pClass->getClassifyByCount($id);
            if($pCount==0){                 
                $returnValue=$pClass->destroy($id);
            }else{
                return  json_encode(['code'=>'0','msg'=>'待删除分类存在下级，请先删除下级分类']);
            }
        }else{
            //批量删除操作
            //根据勾选ID 查找下级ID
            $secondData=$pClass->getCalssifyById($strAry);
            //查找最后一级ID
            $thirdData=$pClass->getCalssifyById($secondData);          
            //删除选中的最后一级ID
            if(count($thirdData)>0){
                 $returnValue=$pClass->deleteClassifyById(array_values(array_intersect($strAry,$thirdData)));   
            }        
          //删除选中的第二级ID
            //判断选中的第二级是否还存下一级
            $scount=$pClass->getClassifyByCount(array_values(array_intersect($strAry,$secondData)));
            if($scount>0){
                return  json_encode(['code'=>'0','msg'=>'待删除分类存在下级，请先删除下级分类']);
            }else{
                if(count($secondData)){
                    $returnValue=$pClass->deleteClassifyById(array_values(array_intersect($strAry,$secondData))); 
                }  
            }
            //判断是否还存在下一级
            $fcount=$pClass->getClassifyByCount($strAry);
            if($fcount>0){
                return  json_encode(['code'=>'0','msg'=>'待删除分类存在下级，请先删除下级分类']); 
            }else{
                $pClass->deleteClassifyById($strAry);  
                $returnValue=$pClass->getClassifyByIdCount($strAry); 
                $returnValue=$returnValue>0?0:1;
            }
         }
         
         if($returnValue>0){
            $code=1;
            $msg="删除成功！";
         }
         return  json_encode(['code'=>$code,'msg'=>$msg]);
    }
    /**
     * checkbox下级选择
     */
    public function checkChild(Request $request){
       return model('Productclassify')->getCheckId($request->param('id'));
    }

}
