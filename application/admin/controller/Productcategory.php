<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Model;


class Productcategory extends Controller
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

        $category = model('Productcategory');
        $data=array();
        $categoryList=array();
        if(!empty($name)){
        	$ndata=array();
            $ndata['category_name']=['like','%'.$name.'%'];//加搜索条件
            $categoryList=$this->sreachList($ndata);           
        }else{
            $categoryList=$this->initList(null);    
        }

        $this->assign('categoryList',$categoryList);
        return $this->fetch();

    }
    public function isRepeat($data,$id){
       foreach ($data as $key => $value) {
           if($value['id']==$id){
               return true;
           }
       }
    }
    /**
     *初始化编辑数据
     * @param  int  $id
     * @return \think\Response
     */
    public function initCategory(Request $request){
        $id=$request->param('id');
        $category = model('Productcategory');
        $data=$category::get(['id'=>$id]);
        return json_encode($data);
    }
     /**
     * 展示全部列表
     */
   public function initList($data){
    $category = model('Productcategory');
    $categoryList=$category->getCategoryByIdPage($data,1);
    foreach ($categoryList as $k=>$v){
        $v['sendList']=$category->getCategoryByIdPage($v['id'],4);
        foreach ($v['sendList'] as $kk=>$vsend){
                $vsend['classifyThree']=$category->getCategoryByIdPage($vsend['id'],4); 
            foreach ($vsend['classifyThree'] as $kkk=>$vfour){
                $vfour['classifyFour']=$category->getCategoryByIdPage($vfour['id'],4);
            }
        }
    }
    return $categoryList;
   }
   /**
    * 根据查询条件显示
    */
   public function sreachList($data){
    $category = model('Productcategory');
    $categoryList=$category->getCategoryByIdPage($data,2);
    $Farray=$Sarray=$Tarray=$Larray=array();
    foreach($categoryList as $k=>$v)
    {
        if($v['parent_id']>0){
            $t=$category->getCategoryByIdPage($v['parent_id'],6);
            if($t['parent_id']>0){
                $tt=$category->getCategoryByIdPage($t['parent_id'],6);
                if($tt['parent_id']>0){
                    $ttt=$category->getCategoryByIdPage($tt['parent_id'],6);
                    $Farray[]=$ttt['id'];
                    $Sarray[]=$tt['id'];
                    $Tarray[]=$t['id'];
                    $Larray[]=$v['id'];
                }else{
                    $Farray[]=$tt['id'];
                    $Sarray[]=$t['id'];
                    $Tarray[]=$v['id'];
                }
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
    $Larray=array_values(array_unique($Larray));
    $categoryList=$category->getCategoryByIdPage($Farray,7);
    foreach($categoryList as $k=>$v){
       foreach($Sarray as $s=>$sv){
           $pid=$category->getCategoryByIdPage($sv,5);
           if($v['id']==$pid['parent_id']){
                 $v['sendList']=$category->getCategoryByIdPage($v['id'],4);
           }
       }
       if(isset($v['sendList'])){
            foreach ($v['sendList'] as $kk => $vv) {
                foreach ($Tarray as $t => $tv) {
                    $pid=$category->getCategoryByIdPage($tv,5);
                        if($vv['id']==$pid['parent_id']){
                            $vv['classifyThree']=$category->getCategoryByIdPage($vv['id'],4);
                        }
                }
                if(isset($vv['classifyThree'])){
                    foreach ($vv['classifyThree'] as $kkk => $vvv) {
                        foreach ($Larray as $l => $lv) {
                            $pid=$category->getCategoryByIdPage($lv,5);
                            if($vvv['id']==$pid['parent_id']){
                                $vvv['classifyFour']=$category->getCategoryByIdPage($vvv['id'],4);
                            } 
                        }
                        if(!isset($vvv['classifyFour'])){$vvv['classifyFour']=[];}
                    }
                }else{
                    $vv['classifyThree']=[]; 
                }
            }
        }
        else{
            $v['sendList']=[];
        }
      
    }
    return $categoryList;
   }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $category = model('Productcategory');
        $request = request();
        $id=$request->param('id');

        //获取一级和二级分类三级
        $categoryList=$category->where('parent_id', '0')->select();
        foreach($categoryList as $k=>&$v)
        {
           
            $v['categorySecond']=$category->getCategoryByIdPage($v['id'],4);
            foreach ($v['categorySecond'] as $kk=>$vv)
            {
                $vv['categorythree']=$category->getCategoryByIdPage($vv['id'],4);
            }
        }
        $this->assign('categoryList',$categoryList);

        $data=array();
        !empty($id) && $data=$category::get($id);
        $this->assign('data',$data);
        return $this->fetch();

    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
             $category = model('Productcategory');
             $data=array(
                'category_name'=>$request->param('name'),
                'id'=>$request->param('id'),
                'parent_id'=>$request->param('parentid'),
                'category_img'=>$request->param('imgurl'),
                'level_type'=>0
            );
                if(empty($data['id'])){//添加
                    $data['create_time']=time();
                    $result=$category->save($data);
                } else {
                    $result=$category->save($data,array('id'=>$data['id']));//更新
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
    /*
     * 图片上传
     * */
    public function uploads()
    {
          // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('up_img');
     // return json_encode($file);
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!empty($file)){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
             if($info){
                $filePath = '/uploads/'.$info->getSaveName();
                 if (!empty($filePath)){
                    $data['state']= 1;
                    $data['savedir']= $filePath;
                     return json_encode($data,JSON_UNESCAPED_SLASHES);
                }else{
                    $data['state']= 0;
                    return json_encode($data);
                }
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
       
        $pCategory=model('Productcategory');
        //字符串转换成数组
        $id=$request->param('id');
        $strAry = array_filter(explode(",",$id));
        $strCount=count($strAry);
        $msg='删除失败！';
        $returnValue=0;
        $code=-1;

        if($strCount==1){
            $pCount=$pCategory->getCategoryByCount($strAry);
           
            if($pCount==0){
                $returnValue=$pCategory->destroy($id);
            }else {
                $code=0;
            }
        }else{
           $error_msg= json_encode(['code'=>$code,'msg'=>$msg]);
           //批量删除处理
           //获取第一级子ID
           $secondData=$pCategory->getCategoryById($strAry);
            //获取第二级子ID
           $thirdData=$pCategory->getCategoryById($secondData); 
           //获取第三级子ID
           $fourData=$pCategory->getCategoryById($thirdData);
           //删除最后一级选中ID
           if(count($fourData)>0){
           
             $returnValue=$this->categoryReturnAndDelete(array_values(array_intersect($strAry,$fourData)));
           }
           //删除第三级选中ID
           if(count($thirdData)>0){

                $returnValue=$this->categoryReturnAndDelete(array_values(array_intersect($strAry,$thirdData)));
                if($returnValue==-1){
                   
                    return json_encode(['code'=>'0','msg'=>'待删除类目存在下级，请先删除下级类目']);
                }
                if($returnValue==0){
                    
                    return  json_encode(['code'=>'-1','msg'=>'删除失败！']);
                }

             } 
           //删除第二级选中ID
           if(count($secondData)>0){
           
                $returnValue=$this->categoryReturnAndDelete(array_values(array_intersect($strAry,$secondData)));
                if($returnValue==-1){
                   
                    return json_encode(['code'=>'0','msg'=>'待删除类目存在下级，请先删除下级类目']);
                }
                if($returnValue==0){
                   
                    return  json_encode(['code'=>'-1','msg'=>'删除失败！']);
                }

            }
           //删除剩余选中ID
           $lcount=$pCategory->getCategoryByCount($strAry);
           //return  json_encode($strAry);
           if($lcount>0){
            
                return  json_encode(['code'=>'0','msg'=>'待删除类目存在下级，请先删除下级类目']);
            }else{
                $pCategory->deleteCategoryById($strAry); 
                $returnValue=$pCategory->getCategoryByIdCount($strAry);
                $returnValue=$returnValue>0?0:1;
            }
        }
        if($returnValue>0){
            $code=1;
            $msg='删除成功！';
         }
         return  json_encode(['code'=>$code,'msg'=>$msg]);
        
    }
    /**
     * 判断是否存在下级并处理
     */
    public function categoryReturnAndDelete($data){
        $pCategory=model('Productcategory');
        $categoryCount=$pCategory->getCategoryByCount($data);
        if($categoryCount>0){
             return  -1;
        }else{
             return $pCategory->deleteCategoryById($data); 
        }
    }
    /**
     * checkbox下级选择
     */
    public function checkChild(Request $request){
        return Model('Productcategory')->getCheckId($request->param('id'));
       
    }
    /**
     * 产品类目级联显示
     */
    public function getCategory(Request $request){
        $pCategory=model('Productcategory');
        $cid=$request->param('cid');
        $count=$pCategory->getCategoryByCount($cid);
        if($count>0){
            $data=$pCategory->getCategoryByIdPage($cid,4);
            return json_encode($data);
        }else{
            return false;
        }
        
        
    }
}
