<?php

namespace app\admin\model;

use think\Model;

class Productmanagement extends Model
{
    //操作产品添加表
    protected $table = 'product_management';


    public function getProductList($valueData){
       //获取产品类目开始
       $category = model('Productcategory');

        $data=$ndata=array();
       if(!empty($valueData['pid'])){
           $data['id']=['like','%'.$valueData['pid'].'%'];//加搜索条件
       }
       if (!empty($valueData['productName'])){
           $data['product_name']=['like','%'.$valueData['productName'].'%'];
       }

       if(!empty($valueData['groupingName'])){
           //获取页面的参数，去分组表查询对应的nanm的id
            $groupingID=model('Productgrouping')->getGroupingById($valueData['groupingName']);

            $ndata=model('Relevancegrouping')->getProductIdForId($groupingID);
            $pid=array();
            foreach($ndata as $k=>$v)
            {
                $pid[]=$v['product_id'];
            }

            !empty($pid) && $data['id']=['in',$pid];
       }
       if(!empty($valueData['labelName'])){
           //获取页面的参数，去分组表查询对应的nanm的id
           $label_nameID =model('Productlabel')->getProductLabelByIdForName($valueData['labelName']);
           $ndata=model('RelevanceLabel')->getRelevanceLabelForPid($label_nameID);
           $pid=array();
           foreach($ndata as $k=>$v)
           {
               $pid[]=$v['product_id'];
           }

           !empty($pid) && $data['id']=['in',$pid];
       }

       if(!empty($valueData['oneId'])&&$valueData['oneId']!='请选择'){
           $data['category_id']=['in',$category->getAllSubCategoryStrByID($valueData['oneId'])];
       }
       if(!empty($valueData['twoId'])&&$valueData['twoId']!='请选择'){
           $data['category_id']=['in',$category->getAllSubCategoryStrByID($valueData['twoId'])];
       }
       if(!empty($valueData['threeId'])&&$valueData['threeId']!='请选择'){
           $data['category_id']=['in',$category->getAllSubCategoryStrByID($valueData['threeId'])];
       }
       if(!empty($valueData['fourId'])&&$valueData['fourId']!='请选择'){
           $data['category_id']=['in',$category->getAllSubCategoryStrByID   ($valueData['fourId'])];
       }

       $data['state']=$valueData['pstate'];

       return $this->getManagementlist($data);
    }
       /**
        * 获取产品类目开始
        */
	  public function getCategoryList(){

           return model('Productcategory')->getCategoryList();
       }

       /**
        * 获取商品信息
        */
       public function getManagementlist($data){

                $managementlist =$this->where($data)->order('id','desc')->paginate(config('paginate.list_rows'));
              
                //获取类目
                foreach ($managementlist as $k=>&$v) {
                    $v['category_name'] = get_category_name_str($v['category_id']);
                    //获取关联标签表对应产品id的标签的id
                    $labei_id =model('RelevanceLabel')->getRelevancelLabelListForID($v['id']);
                   //获取关联分组表对应产品id的分组id
                    $grouping_id=model('Relevancegrouping')->getRelevanceGroupingForId($v['id']);
                    $g_name=array();
                    foreach ($grouping_id as $key=>$value){
                        $tmp=model('Productgrouping')->getGroupingListForId($value['product_grouping_id']);
                        $g_name[$key]['grouping_name']=$tmp['grouping_name'];
                    }
                    $v['grouping_list'] = $g_name;
                    $l_name=array();
                    foreach ($labei_id as $kk=>$vv){
                        $tmp=model('Productlabel')->getProductLabelForId($vv['product_label_id']);
                        $l_name[$kk]['product_name']=$tmp['product_name'];
                    }
                    $v['label_list'] = $l_name;
                }
                return $managementlist;
       }

       public function getMenuForMenuName($data)
       {
           $returnValue=model('Adminmenu')->getMenuForMenuName($data);

          if(empty($returnValue)){
            $returnValue='产品清单';
          } 
           return $returnValue;

       }
       public function getProductcategory($data)
       {
           $cid=$this->where('id',$data)->value('category_id');
           $returnValue=model('Productcategory')->getProductcategory($cid);
           return json_encode($returnValue);
       }

	
    //校验下首单产品的数量
	public function getFirstOrderProductCount($pid=0)
    {
    	$data=array();
    	$data['is_first_order']=1;
    	$data['state']=1;
    	$pid>=0 && $data['id']=['neq',$pid];
    	 
    	return $this->where($data)->count();
    }
    
    //获取收到产品ID
    public function getFirstOrderProductId()
    {
    	return $this->where('is_first_order=1 and state=1')->value('id');
    }
}
