<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Productmanagement extends Common
{


    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $management = model('Productmanagement');
        $request = request();
        $id=$request->param('id');

        $state=$request->param('p_state');

        if($state==null){
            $state=1;

        }
        $state=(int)$state;
        $product_name=$grouping_name=$label_name=$one_id=$two_id=$three_id=$four_id=0;

        $request->param('product_name') && $product_name=$request->param('product_name');
        $request->param('grouping_name') && $grouping_name=$request->param('grouping_name');
        $request->param('label_name') && $label_name = $request->param('label_name');

        $request->param('type_id')&& $one_id=$request->param('type_id');
        $request->param('two_id') && $two_id=$request->param('two_id');
        $request->param('three_id') && $three_id=$request->param('three_id');
        $request->param('four_id') && $four_id=$request->param('four_id');

        $data=array(
            'pid'=>$id,
            'pstate'=>$state,
            'productName'=>$product_name,
            'groupingName'=>$grouping_name,
            'labelName'=>$label_name,
            'oneId'=>$one_id,
            'twoId'=>$two_id,
            'threeId'=>$three_id,

            'fourId'=>$four_id
        );
        $categoryList=$management->getCategoryList();


        $managementlist=$management->getProductList($data);
        //循环取出销量值sales_volume为实际销量，false_volume为后台设置销量
        foreach ($managementlist as $k=>&$value){
            $value['mix_volume'] = $value['false_volume']+$value['sales_volume'];
        }


        $this->assign('categoryList',$categoryList);//类目数据
        $this->assign('managementlist',$managementlist);

        //分配页面搜索值
        $this->assign('product_name',$product_name);
        $this->assign('grouping_name',$grouping_name);
        $this->assign('label_name',$label_name);
        $this->assign('one_id',$one_id);
        $this->assign('two_id',$two_id);
        $this->assign('three_id',$three_id);
        $this->assign('four_id',$four_id);
        $this->assign('state',$state);
        $this->assign('menu_name',$management->getMenuForMenuName('Productmanagement')['name']);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //获取奖励机制的开关数据
        $config = model('Agentrewardconfig')->order('id','desc')->find();
        // 标签表
        $labelList= model('Productlabel')->select();
        //获取分组表
        $groupinglist=model('Productgrouping')->order('id','desc')->select();
        //获取类目列表顶级类目
        $categoryList= model('Productcategory')->where('parent_id', '0')->order('id', 'desc')->select();

        //推荐奖励配置列表
        $recommendInfo=array();
        $recommend=model('Agentrewardrecommend');
        $recommendInfo=$recommend->order('id','desc')->select();
        $this->assign('recommendInfo',$recommendInfo);

        //代理商奖励表里面奖励比例
        $agencyList=model('Agentlevel')->where('valid=1')->order('id','desc')->select();

        //需要代理商奖励的名称
        $listLevel = model('Agentlevel')->where('valid=1')->order('id','desc')->select();

        foreach($listLevel as $k=>&$v) {

            $tmpValueAry=$tmpIdAry=array();//不能直接引用，使用中间量
            for($i=1;$i<$v['deep']+1;$i++) {
                $recommendTmp=$recommend->where(array('role'=>$v['id'],'hierarchy'=>$i))->find();
                if(!empty($recommendTmp['value'])){
                    $tmpValueAry[$i]=$recommendTmp['value'];
                    $tmpIdAry[$i]=$recommendTmp['id'];

                }
                $v['roleRatioValue']=$tmpValueAry;
                $v['roleRatioId']=$tmpIdAry;
            }

        }
        $this->assign('config',$config);// 获取奖励机制的开关数据
        $this->assign('listLevel',$listLevel);//代理商等级名称

        $this->assign('agencyList',$agencyList);//代理商奖励
        $this->assign('categoryList',$categoryList);
        $this->assign('labelList',$labelList);//标签数据
        $this->assign('groupinglist',$groupinglist);//分组表数据

        // 2018-07-13 CYL 运费模板
        $express = model('Expresstemplete')->getAllList();
        $this->assign('express',$express);
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

         //获取奖励机制的开关数据
        $rewardconfig = model('Agentrewardconfig');
        $config=$rewardconfig->order('id','desc')->find();

        $management = model('Productmanagement');
        //获取产品标签传过来的id
        $label_arr = input('post.checkbox1/a');
        //获取产品分组传的id
        $grouping_arr = input('post.checkbox/a');

        $level=model('Agentlevel');
        $listLevel=$level->where('valid=1')->order('id','desc')->select();
        //产品招商奖励关联表
        $product_recommend=model('Productrecommendreward');

        //代理商奖励表里面奖励比例
        $agency=model('Agentrewardagency');
        $agencyList=$agency->order('id','desc')->select();

        $img_id=$request->param('img_sing');//页面返回的路径

        //替换上传路径中的域名
        $url = 'http://'.$_SERVER['HTTP_HOST'];

        $img_id=str_replace($url,'',$img_id);

        $img_arr = explode('|',$img_id);

        $img_count = count($img_arr);

        $four_id=$request->param('f_id');
        $three_id=$request->param('t_id');
        $two_id=$request->param('s_id');
        $type_id=$request->param('type_id');


        $date=array();
        if (!empty($four_id) && $four_id !=='请选择'){
            $date=array(
                'category_id'=>$request->param('f_id'),//四级类目
            );
        }else if (!empty($three_id)&&$three_id !=='请选择'){
             $date=array(
                'category_id'=>$request->param('t_id'),//三级类目
             );
        }else if (!empty($two_id)&&$two_id !=='请选择'){
             $date=array(
                'category_id'=>$request->param('s_id'),//二级类目
             );
        }else if (!empty($type_id)&&$type_id !=='请选择'){
             $date=array(
                'category_id'=>$request->param('type_id'),//一级类目
             );
        }

        if($request->method()=='POST') {

            $data=array(
                'product_name'=>$request->param('product_name'),//产品名称
                'product_img'=>$request->param('pic_url'),//封面图片
                'explain'=>$request->param('explain'),//封面简介
                'sales_price'=>$request->param('sales_price'),//销售价
                'cost_price'=>$request->param('cost_price'),//成本价
                'unit'=>$request->param('unit'),//单位
                'weight'=>$request->param('weight'),//重量
                'inventory'=>$request->param('inventory'),//库存
                'false_volume'=>$request->param('false_volume'),//售出
                'details'=>$request->param('content'),//详情说明
                'specification'=>$request->param('content2'),//产品规格
                'express' => $request->param('express'),// 2018-07-13 CYL 运费模板
            	'is_agent_level' => $request->param('is_agent_level')//代理商等级的商品
            );
            if (empty($data['product_img'])){
                unset($data['product_img']);
            }
            isset($date['category_id']) && $data['category_id']=$date['category_id'];
            //获取包邮，首单，是否大礼包商品
            $exemption_from_postage=$is_gift=$is_first_order=1;

            // $exemption_from_postage=$request->param('exemption_from_postage');
            $is_gift=$request->param('is_gift');
            $is_first_order=$request->param('is_first_order');
            $is_Purchase_a=$request->param('is_Purchase_a');//是否限购一件
        

            //精简下代码
            $data['is_gift']=$data['is_first_order']=$data['is_Purchase_a']=2;
            // $exemption_from_postage=='on' && $data['exemption_from_postage']=1;
            $is_gift=='on' && $data['is_gift']=1;
            $is_first_order=='on' && $data['is_first_order']=1;
            $is_Purchase_a=='on' && $data['is_Purchase_a']=1;
            

            $productCount=$management->getFirstOrderProductCount();
            if($productCount>=1 && $data['is_first_order']==1){
            	$this->error('首单产品只能设置一个');
            	exit();
            }

            //数据校验
            $validate = validate('Productmanagement');

            if(!$validate->check($data)){
                $this->error($validate->getError());
            } else {
                $result=0;

                if(empty($data['id'])){//添加

                    $data['create_time']=time();
                    $data['state']=0;
                    if($img_count>9){
                        $this->error('您上传的图片数量大于九张，请重新上传!'); exit();
                    }else{
                        $data_img['more_img']=$img_id;

                        $pid=$management->insertGetId($data);
                        if (!empty($data_img['more_img'])&& $pid){
                            $tmpAry = explode('|', $data_img['more_img']);
                            foreach ($tmpAry as $k => $v) {
                                $tdata = array();
                                $tdata['product_id'] = $pid;
                                $tdata['img']= $v;
                                $tdata['time']=time();
                                $nid=db('product_many_img')->insertGetId($tdata);
                            }
                        }
                    }

                    //代理商的添加，判断开启，添加产品如果有值就用添加的值，默认-1的时候添加代理商表里面的数据
                    //代理商奖励1是开始2是关闭
                    if ($config['valid_agency_reward'] == 1) {
                        //代理商奖励表里面奖励比例
                        $agencyinfo=model('Productagentreward');
                        $agency=model('Agentrewardagency');

                        //获取代理商页面数据源
                        $data_agency=array();
                        $k=0;
                        //等级列表,如果有数据，那么获取
                        $level=model('Agentlevel');
                        $listLevel=$level->where('valid=1')->order('id','desc')->select();

                        foreach($listLevel as $k=>&$v)
                        {
                            $data_agency[$k]['agent_reward'] = $request->param('agency_value_'.$v['id']);
                            $data_agency[$k]['role'] = $request->param('agency_role_'.$v['id']);
                            $data_agency[$k]['product_id'] = $pid;
                            $k++;
                        }

                        foreach ($data_agency as $k=>&$valuew){
                            if ($valuew['agent_reward'] == -1){
                                $tmp=$agency->where(['role'=>$valuew['role']])->value('ratio');
                                $valuew['agent_reward']=$tmp;
                            }
                            if($valuew['agent_reward']==0) {
                            	unset($data_agency[$k]);
                            }
                        }

                        $agencyinfo->insertAll($data_agency);
                    }


                    //招商奖励的添加，判断开启，添加产品如果有值就用添加的值，默认-1的时候添加推荐表里面的数据
                    //招商奖励1是开始2是关闭
                    if ($config['valid_recommend_reward'] == 1){

                        //获取等级数据的列表，需要批量获取数据
                        $data_set=array();
                        $level=model('Agentlevel');//身份层级表，1代表开启2代表关闭
                        $recommend=model('Agentrewardrecommend');
                        $listLevel=$level->where('valid=1')->order('id','desc')->select();

                        //循环获取数据存储
                        $j=0;
                        foreach($listLevel as $k=>$v)
                        {
                            for($i=1;$i<$v['deep']+1;$i++) {
                                $data_set[$j]['role']=$v['id'];
                                $data_set[$j]['hierarchy']=$i;
                                $data_set[$j]['value']=$request->param('value_'.$v['id'].'_'.$i);
                                $data_set[$j]['product_id'] = $pid;
                                $j++;
                            }
                        }

                        foreach ($data_set as $k=>&$values){
                            if ($values['value'] == -1){
                                $tmp=$recommend->where(['role'=>$values['role'],'hierarchy'=>$values['hierarchy']])->value('value');
                                $values['value']=$tmp;
                            }
                        }
                        $product_recommend->insertAll($data_set);
                    }
                    //循环产品标签id加入到关联表里面relevance_label
                    if (!empty($label_arr)){
                        foreach ($label_arr as $v) {
                            $label = array(
                                'product_id' => $pid,
                                'product_label_id' => $v
                            );
                            //添加到产品标签表。
                            $result= db('relevance_label')->insert($label);
                        }
                    }

                    if (!empty($grouping_arr)){
                        foreach ($grouping_arr as $v){
                            $grouping = array(
                                'product_id' => $pid,
                                'product_grouping_id'=>$v
                            );
                            //添加到产品标签表。
                            $result= db('relevance_grouping')->insert($grouping);
                        }
                    }
                }
                $this->success('操作成功', '/admin/Productmanagement/index/p_state/0');
            }
        }
    }

    public function delPic()
    {
        $request = request();
        $res=0;
        $id=$request->param('key');
        if(!empty($id)) {
            $res=db('product_many_img',null)->where('id='.$id)->delete();
        }
        echo json_encode($res);
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //产品列表
        $management = model('productmanagement');
        $request = request();
        if($request->method()=='GET') { //单个删除的方法
            $id=$request->param('id');
            $result=0;
            $result=$management->destroy($id);
            if($result==0){
                return json_encode(['code'=>0,'msg'=>'删除失败！']);

            } else {
                return json_encode(['code'=>1,'msg'=>'删除成功','url'=>'/admin/Productmanagement/index/p_state/0']);
                // $this->success('操作成功', '/admin/Productmanagement/index/');
            }
        } else if ($request->method()=='POST'){ //批量删除POST提交方法

            $id=$request->param('id');
            //字符串转换成数组
            $str = explode(" ",$id);
            $result=0;
            //循环删除
            foreach ($str as $k=>$value){
                $result=$management->destroy($value);
            }
            if ($result==0){
                $data['status']='on';
                return json_encode($data);
            }else{
                $data['status']='ok';
                return json_encode($data);
            }
        }
        $this->error('非法操作，请重试');
    }
    /*
     * 展示编辑新方法
     * */
    public function createEdit()
    {
        $request = request();
        $id=$request->param('id');//get页面的产品ID
        //获取产品分组表
        $grouping_top = model('Productgrouping');
        $grouping_data =  $grouping_top->order('id','desc')->select();
        //获取产品关联分组表
        $grouping = model('Relevancegrouping');
        $groupinglist=$grouping->where('product_id',$id)->order('id','desc')->select();

        $grouping_id=array();
        foreach ($groupinglist as $key=>$value){
            $tmp=$grouping_top->where('id',$value['product_grouping_id'])->find();
            $grouping_id[]=$tmp['id'];
        }

        // 标签表
        $label_top = model('Productlabel');
        $label_data =  $label_top->order('id','desc')->select();

        // 关联标签表
        $label = model('RelevanceLabel');
        $labelList=$label->where('product_id',$id)->select();
        $level_id=array();
        foreach ($labelList as $k=>$vale){
            $tm = $label_top->where('id',$vale['product_label_id'])->find();

            $level_id[]=$tm['id'];
        }

        //产品列表
        $management = model('Productmanagement');
        $productmanyimg =model('Productmanyimg');
        $info_arr = $productmanyimg->where('product_id',$id)->select();
        $this->assign('info_arr',$info_arr);
        //获取类目数据
        $category=model('Productcategory');
        $categoryID = $management->where('id',$id)->value('category_id');

        $topLevelAry=$category->getTopLevelInfo($categoryID);

        $data=array();
        !empty($id) && $data=$management::get($id);

        //推荐奖励配置列表
        $recommend=model('Productrecommendreward');
        //需要招商奖励的名称
        $level_name=model('Agentlevel');
        $listLevel = $level_name->where('valid=1')->order('id','desc')->select();

        $rewardRecommend=model('Agentrewardrecommend');

        foreach($listLevel as $k=>&$v) {
            $tmpValueAry=$tmpIdAry=array();//不能直接引用，使用中间量
            for($i=1;$i<$v['deep']+1;$i++) {
                $recommendTmp=$recommend->where(array('role'=>$v['id'],'hierarchy'=>$i,'product_id'=>$id))->find();
                //在编辑产品的时候，如果开启新的招商奖励身份时，使他默认为-1
                $tmpValueAry[$i]='-1';
                $tmpIdAry[$i]=0;
                if(!empty($recommendTmp['value'])){
                    $rewardValue=$rewardRecommend->where(array('role'=>$v['id'],'hierarchy'=>$i))->value('value');
                    $rewardValue==$recommendTmp['value'] && $recommendTmp['value']='-1';
                    $tmpValueAry[$i]=$recommendTmp['value'];
                    $tmpIdAry[$i]=$recommendTmp['id'];
                }

                $v['roleRatioValue']=$tmpValueAry;
                $v['roleRatioId']=$tmpIdAry;
            }
        }

        //代理商奖励表里面奖励比例
        $agency=model('Productagentreward');
        $agencyret=model('Agentrewardagency');

        $agencyList=$level_name->where('valid=1')->order('id','desc')->select();

        foreach ($agencyList as $k=>&$valuep){

        	//获取数据
        	$tmp=$agency->where('product_id='.$id.' and role='.$valuep['id'])->find();

        	isset($tmp['agent_reward']) && $valuep['agent_reward']=$tmp['agent_reward'];
        	isset($tmp['product_id']) && $valuep['product_id']=$tmp['product_id'];
        	isset($tmp['role']) && $valuep['role']=$tmp['role'];
        	isset($tmp['id']) && $valuep['agency_id']=$tmp['id'];

        	//显示-1
        	if(isset($valuep['role'])) {
	            $count=$agencyret->where(['role'=>$valuep['role'],'ratio'=>$valuep['agent_reward']])->count();
	            $count == 1  && $valuep['agent_reward'] = -1;
        	}
        }

        //获取奖励机制的开关数据
        $rewardconfig = model('Agentrewardconfig');
        $config=$rewardconfig->order('id','desc')->find();
        //获取类目列表顶级类目
        $category = model('Productcategory');
        $categoryList=$category->where('parent_id', '0')->order('id', 'desc')->select();
        //echo $category->getLastSql();exit;

        $this->assign('label_data',$label_data);//标签数据
        $this->assign('grouping_data',$grouping_data);//分组表数据
        $this->assign('grouping_id',$grouping_id);
        $this->assign('level_id',$level_id);
        $this->assign('config',$config);// 获取奖励机制的开关数据

        $this->assign('listLevel',$listLevel);//招商奖励

        $this->assign('agencyList',$agencyList);//代理商奖励

        $typeList=$this->sortList($topLevelAry[0]);

        $secondList=$threeList=$fourList=array();
        isset($topLevelAry[1]) && $secondList=$this->secondList($topLevelAry[0],$topLevelAry[1],2);
        isset($topLevelAry[2]) && $threeList=$this->secondList($topLevelAry[1],$topLevelAry[2],3);
        isset($topLevelAry[3]) && $fourList=$this->secondList($topLevelAry[2],$topLevelAry[3],4);

        $this->assign('typeList',$typeList);
        $this->assign('secondList',$secondList);
        $this->assign('threeList',$threeList);
        $this->assign('fourList',$fourList);
        $this->assign('categoryID',$categoryID);
        $this->assign('topLevelAry',$topLevelAry);

        $this->assign('data',$data);
        // 2018-07-13 CYL 运费模板
        $express = model('Expresstemplete')->getAllList();
        $this->assign('express',$express);
        return $this->fetch('createEdit');

    }
    //多级分类列表
    public function sortList($type_id=0)
    {

        $category = model('Productcategory');
        $topList=$category->where('parent_id', '0')->select();

        $str='';
        foreach($topList as $key=>$value)
        {
            if($value['id']==$type_id) {
                $str.="<option selected='selected' value=".$value['id']." id='categoryName' name='categoryName'>".$value['category_name']."</option>";
            } else {
                $str.="<option value=".$value['id'].">".$value['category_name']."</option>";
            }

        }
        return $str;
    }
    public function secondList($top,$type_id,$level)
    {
        $res=array();
        $res['level']=$level;

        $category = model('Productcategory');
        $topList=$category->where('parent_id='.$top)->select();

        $res['str']='';
        foreach($topList as $key=>$value)
        {
            if($value['id']==$type_id) {
                $res['str'].="<option selected='selected' value=".$value['id']." id='categoryName' name='categoryName'>".$value['category_name']."</option>";
            } else {
                $res['str'].="<option value=".$value['id'].">".$value['category_name']."</option>";
            }

        }
        return $res;
    }
    //保存
    public function saveEdit()
    {
        $request = request();
        //获取奖励机制的开关数据
        $rewardconfig = model('Agentrewardconfig');
        $config=$rewardconfig->order('id','desc')->find();
        //产品表
        $management = model('Productmanagement');
        //关联产品标签表
        $RelevanceLabel = model('RelevanceLabel');
        //关联产品分组表
        $Relevancegrouping = model('Relevancegrouping');
        //产品招商奖励关联表
        $product_recommend=model('Productrecommendreward');
        //获取多图的全部id组装以后判断，最后更新
        $productmanyimg =model('Productmanyimg');
        $img_info=$request->param('img_sing');
        //替换上传路径中的域名
        $url = 'http://'.$_SERVER['HTTP_HOST'];

        $img_info=str_replace($url,'',$img_info);

        $img_arr = explode('|',$img_info);

        $img_count = count($img_arr);
        //获取产品标签传过来的id
        $label_arr = input('post.checkbox1/a');

        //获取产品分组传的id
        $grouping_arr = input('post.checkbox/a');

        //代理商奖励表里面奖励比例
        $agencyinfo=model('Productagentreward');


        //获取产品类目id，然后判断，把最小级别的id更新数据库
        $four_id=$request->param('f_id');
        $three_id=$request->param('t_id');
        $two_id=$request->param('s_id');
        $type_id=$request->param('type_id');

        if (!empty($four_id) && $four_id !=='请选择'){
            $date=array(
                'category_id'=>$request->param('f_id'),//四级类目
            );
        }else if (!empty($three_id)&&$three_id !=='请选择'){
            $date=array(
                'category_id'=>$request->param('t_id'),//三级类目
            );
        }else if (!empty($two_id)&&$two_id !=='请选择'){
            $date=array(
                'category_id'=>$request->param('s_id'),//二级类目
            );
        }else if (!empty($type_id)&&$type_id=='请选择'){
            $date=array(
                'category_id'=>$request->param('type_id'),//一级类目
            );
        }

        if($request->method()=='POST')
        {
            $data=array(
                'id'=>$request->param('id'),//产品id
                'product_name'=>$request->param('product_name'),//产品名称
                'product_img'=>$request->param('pic_url'),//封面图片
                'explain'=>$request->param('explain'),//封面简介
                'sales_price'=>$request->param('sales_price'),//销售价
                'cost_price'=>$request->param('cost_price'),//成本价
                'unit'=>$request->param('unit'),//单位
                'weight'=>$request->param('weight'),//重量
                'inventory'=>$request->param('inventory'),//库存
                'false_volume'=>$request->param('false_volume'),//售出
                'details'=>$request->param('content'),//详情说明
                'specification'=>$request->param('content2'),//产品规格
                'express' => $request->param('express'), // 2018-07-13 CYL 运费模板
            	'is_agent_level' => $request->param('is_agent_level')//代理商等级的商品
            );
            isset($date['category_id']) && $data['category_id']=$date['category_id'];

            //如果库存为0，那么设置自动下架
            if($data['inventory']==0) {
                $ndata=array();
                $ndata['state']=0;
                $management->save($ndata,['id'=>$data['id']]);
            }

            //获取包邮，首单，是否大礼包商品

            $is_gift=$request->param('is_gift');
            $is_first_order=$request->param('is_first_order');
            $is_Purchase_a=$request->param('is_Purchase_a');//是否限购一件
         

            //精简下代码
            $data['is_gift']=$data['is_first_order']=$data['is_Purchase_a']=2;
            $is_gift=='on' && $data['is_gift']=1;
            $is_first_order=='on' && $data['is_first_order']=1;
            $is_Purchase_a=='on' && $data['is_Purchase_a']=1;
        

            $productCount=$management->getFirstOrderProductCount($data['id']);
            if($productCount>=1 && $data['is_first_order']==1){
            	$this->error('首单产品只能设置一个');
            	exit();
            }

            //数据校验
            $validate = validate('Productmanagement');
            $verify = $validate->scene('saveEdit')->check($data);
            if(!$verify)
            {
                $this->error($validate->getError());
            }else{
                $result=0;
                if(!empty($data['id'])){
                    $pid=$data['id'];
                    //更新多图
                    if($img_count>9){
                        $this->error('您上传的图片数量大于九张，请重新上传!'); exit();
                    }else{

                        $data_img['more_img']=$img_info;
                        if (!empty($data_img['more_img'])&& $pid){

                            $tmpAry = explode('|',$data_img['more_img']);

                            $productmanyimg->where('product_id',$pid)->delete();
                            foreach ($tmpAry as $k => $v) {
                                $data_info=array();
                                $data_info['product_id']=$pid;
                                $data_info['img']=$v;
                                $data_info['time']=time();
                                $result = $productmanyimg->insert($data_info);
                            }
                        }
                    }

                    //招商奖励的添加，判断开启，添加产品如果有值就用添加的值，默认-1的时候添加推荐表里面的数据
                    //招商奖励1的时候设置开启 2的状态是关闭
                    if ($config['valid_recommend_reward'] == 1){
                        //获取等级数据的列表，需要批量获取数据
                        $data_set=array();
                        $level=model('Agentlevel');
                        $recommend=model('Agentrewardrecommend');
                        $listLevel=$level->where('valid=1')->order('id','desc')->select();

                        //循环获取数据存储
                        $j=0;
                        foreach($listLevel as $k=>$v)
                        {
                            for($i=1;$i<$v['deep']+1;$i++) {
                            	$data_set[$j]['product_id']=$pid;
                                $data_set[$j]['role']=$v['id'];
                                $data_set[$j]['hierarchy']=$i;
                                $data_set[$j]['value']=$request->param('value_'.$v['id'].'_'.$i);
                                $tmpId=$request->param('id_'.$v['id'].'_'.$i);
                                $tmpId>0 &&  $data_set[$j]['id']=$tmpId;
                                $j++;
                            }
                        }

                        foreach ($data_set as $k=>&$values){
                            if ($values['value'] == -1){
                            	$tmp=0;
                                $tmp=$recommend->where(['role'=>$values['role'],'hierarchy'=>$values['hierarchy']])->value('value');

                                empty($tmp) && $values['value']=0;
                                $tmp>0 && $values['value']=$tmp;
                            }

                        }

                        $product_recommend->saveAll($data_set);
                    }
                    //代理商的添加，判断开启，添加产品如果有值就用添加的值，默认-1的时候添加代理商表里面的数据
                    //代理商1是开启2是关闭
                    if ($config['valid_agency_reward'] == 1) {
                        $agency=model('Agentrewardagency');
                        //获取代理商页面数据源
                        $data_agency=array();
                        $k=0;
                        //等级列表,如果有数据，那么获取
                        $level=model('Agentlevel');
                        $listLevel=$level->where('valid=1')->order('id','desc')->select();
                        foreach($listLevel as $k=>&$v)
                        {
                            $data_agency[$k]['agent_reward'] = $request->param('value_'.$v['id']);
                            $data_agency[$k]['role'] = $request->param('role_'.$v['id']);
                            $data_agency[$k]['product_id'] =$pid;

                            //有的更新，没有的插入
                            $tmp=$request->param('aid_'.$v['id']);
                            $tmp>0 && $data_agency[$k]['id'] = $tmp;
                            $k++;
                        }

                        foreach ($data_agency as $k=>&$valuew){
                            if ($valuew['agent_reward'] == -1){

                            	$tmp=0;
                                $tmp=$agency->where(['role'=>$valuew['role']])->value('ratio');

                                empty($tmp) && $valuew['agent_reward']=0;
                                $tmp>0 && $valuew['agent_reward']=$tmp;
                            }
                        }

                        $agencyinfo->saveAll($data_agency);
                    }
                    //获取标签id数组
                    if (!empty($label_arr)){

                        //更新到产品标签表。
                        $ret=$RelevanceLabel->where('product_id',$pid)->delete();

                        if(!empty($ret)){
                            $labeldata=array();
                            foreach ($label_arr as $k=>$va){
                                $labeldata[$k]=array('product_label_id' =>$va, 'product_id' =>$pid);
                            }

                            $result= $RelevanceLabel->insertAll($labeldata);
                        }else{
                            $labeldata=array();
                            foreach ($label_arr as $k=>$va){
                                $labeldata[$k]=array('product_label_id' =>$va, 'product_id' =>$pid);
                            }
                            $result= $RelevanceLabel->insertAll($labeldata);
                        }
                    }
                    //获取分组id数组
                    if (!empty($grouping_arr)){
                        //更新到产品标签表。
                        $ret=$Relevancegrouping->where('product_id',$pid)->delete();
                        if(!empty($ret)){
                            $groupingdata=array();
                            foreach ($grouping_arr as $k=>$va){
                                $groupingdata[$k]=array('product_grouping_id' =>$va, 'product_id' =>$pid);
                            }
                            $result= $Relevancegrouping->insertAll($groupingdata);
                        }else{
                            $groupingdata=array();
                            foreach ($grouping_arr as $k=>$va){
                                $groupingdata[$k]=array('product_grouping_id' =>$va, 'product_id' =>$pid);
                            }
                            $result= $Relevancegrouping->insertAll($groupingdata);
                        }
                    }
                }
                $result=$management->save($data,array('id'=>$pid));//更新
            }
            $this->success('更新成功', '/admin/Productmanagement/index/');
        }
    }

    //上下架功能
    public function set_up_down()
    {
        $request = request();
        $id=$request->param('id');
        $state= $request->param('state');
        //产品列表
        $management = model('productmanagement');
        if(!empty($state)){
            $management->where('id',$id)->setField('state','0');
            $management->where('id',$id)->setField('end_time',time());
            return json_encode(['code'=>1,'msg'=>'下架成功','url'=>"/admin/Productmanagement/index/p_state/1"]);
        }else{

            //校验下库存，如果为0，那么上架失败
            $productInfo=$management->find($id);
            if($productInfo['inventory']>0) {
                $management->where('id',$id)->setField('state','1');
                $management->where('id',$id)->setField('putaway_time',time());
                return json_encode(['code'=>1,'msg'=>'上架成功','url'=>'/admin/Productmanagement/index/p_state/0']);
            } else {
                return json_encode(['code'=>0,'msg'=>'上架失败，库存不能为0','url'=>'/admin/Productmanagement/index/p_state/0']);


            }
        }

    }
    //批量导出excel
    public function excelProductList()
    {
        $title=array('产品ID','产品名称','类目','组别','售价','成本价','出货量','库存','标签','上架时间');
        $filename='产品列表';
        //产品表
        $management = model('Productmanagement');
        //关联标签表
        $relevancelabel = model('RelevanceLabel');
        //标签表
        $labelname=model('Productlabel');
        //关联分组表
        $grouping = model('Relevancegrouping');
        //分组表
        $groupingname  = model('Productgrouping');
        //获取产品类目开始
        $category = model('Productcategory');

        $request=request();


        $product_name=$request->param('product_name');

        $grouping_name=$request->param('grouping_name');
        $label_name=$request->param('label_name');

        $one_id=$request->param('one_id');
        $two_id=$request->param('two_id');
        $three_id=$request->param('three_id');
        $four_id=$request->param('four_id');

        $data = array();
        $state=$request->param('state');
        $state_r=$request->param('state_r');
        !empty($product_name) && $data['product_name']=$product_name;
        if(!empty($grouping_name)){
            $groupingID = $groupingname->where('grouping_name',$grouping_name)->value('id');//获取页面的参数，去分组表查询对应的nanm的id

            $ndata = $grouping->where('product_grouping_id',$groupingID)->distinct(true)->field('product_id')->select();
            $pid=array();
            foreach($ndata as $k=>$v)
            {
                $pid[]=$v['product_id'];
            }

            !empty($pid) && $data['id']=['in',$pid];
        }
        if(!empty($label_name)){
            $label_nameID = $labelname->where('product_name',$label_name)->value('id');//获取页面的参数，去分组表查询对应的nanm的id

            $ndata = $relevancelabel->where('product_label_id',$label_nameID)->distinct(true)->field('product_id')->select();
            $pid=array();
            foreach($ndata as $k=>$v)
            {
                $pid[]=$v['product_id'];
            }

            !empty($pid) && $data['id']=['in',$pid];
        }
        if(!empty($one_id)&&$one_id!='请选择'){
            $data['category_id']=['in',$category->getAllSubCategoryStrByID($one_id)];
        }
        if(!empty($two_id)){
            $data['category_id']=['in',$category->getAllSubCategoryStrByID($two_id)];
        }
        if(!empty($three_id)){
            $data['category_id']=['in',$category->getAllSubCategoryStrByID($three_id)];
        }
        if(!empty($four_id)){
            $data['category_id']=['in',$category->getAllSubCategoryStrByID($four_id)];
        }
        if (!empty($state)){
            $data['state'] = 1;
        }
        if (!empty($state_r)){
            $data['state'] = 0;
        }
        $managementList = $management->where($data)->order('id','desc')->select();
        $data = array();
        foreach ($managementList as $k=>$v)
        {
            $data[$k]['id'] = $v['id'];
            $data[$k]['product_name'] = $v['product_name'];
            $data[$k]['category_name'] = get_category_name_str($v['category_id']);

            $grouping_id= $grouping->where('product_id',$v['id'])->select();//获取关联分组表对应产品id的分组id
            $grouping_name=array();
            foreach ($grouping_id as $key=>$value){
                $tmp=$groupingname->where('id',$value['product_grouping_id'])->find();
                $grouping_name[]=$tmp['grouping_name'];
            }
            $data[$k]['grouping_name'] = implode(",", $grouping_name);

            $data[$k]['sales_price'] = $v['sales_price'];
            $data[$k]['cost_price'] = $v['cost_price'];
            $data[$k]['sales_volume'] = $v['sales_volume'];
            $data[$k]['inventory'] = $v['inventory'];
            $labei_id = $relevancelabel->where('product_id', $v['id'])->select();//获取关联标签表对应产品id的标签的id
            $label_name=array();
            foreach ($labei_id as $kk=>$vv){
                $tmp=$labelname->where('id',$vv['product_label_id'])->find();
                $label_name[]=$tmp['product_name'];
            }
            $data[$k]['label_list'] = implode(",", $label_name);

            $data[$k]['create_time'] = $v['create_time'];
        }
        $this->exportexcel($data,$title,$filename);
    }
    /*
     * excel导入方法
     * */
    public function insertExcel()
    {
        //产品列表
        $management = model('Productmanagement');
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        //获取表单上传文件
        $file = request()->file('excelfile');
        if (!empty($file)){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'excel');
            if ($info){
                $exclePath = $info->getSaveName();  //获取文件名

                $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $exclePath;   //上传文件的地址

                $objReader =\PHPExcel_IOFactory::createReader('Excel2007');

                $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                echo "<pre>";
                $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                array_shift($excel_array);  //删除第一个数组(标题);
                $data = [];
                $i=0;
                foreach($excel_array as $k=>$v) {
                    $data[$k]['product_name'] = $v[0];//产品名称
                    $data[$k]['sales_price'] = $v[1];//销售价格
                    $data[$k]['cost_price'] = $v[2];//成本价格
                    $data[$k]['unit'] = $v[3];//单位
                    $data[$k]['weight'] = $v[4];//重量
                    $data[$k]['inventory'] = $v[5];//库存
                    $data[$k]['explain'] = $v[6];//封面图片简短说明
                    $data[$k]['details'] = $v[7];//详情说明
                    $data[$k]['specification'] = $v[8];//产品规格
                    $data[$k]['state'] = 0;
                    $data[$k]['create_time']=time();
                    $i++;
                }
                $success = $management->insertAll($data);
                $error=$i-$success;
                echo "总{$i}条，成功{$success}条，失败{$error}条。";
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
            $this->success('操作成功', '/admin/Productmanagement/index/');
        }

        return $this->fetch('insertExcel');
    }
    /*
     * excel下载方法
     * */
    public function download()
    {
        $file_name = 'product_template'.".xlsx";     //下载文件名

        $file_dir = dirname(ROOT_PATH . 'public' . DS . 'download')."/download/"; //下载文件存放目录

        //检查文件是否存在
        if (!file_exists ($file_dir.$file_name)) {
            echo "文件找不到";
            exit ();
        } else {
            header("Content-Type: application/force-download");//强制下载
            //给下载的内容指定一个中文名字
            header("Content-Disposition: attachment; filename=".$file_name);
            readfile($file_dir.$file_name);
        }
    }
    //点击生成产品二维码
    public function producCode()
    {
        $request=request();
        $id=$request->param('id');
        $time = time();
        $path = "uploads/produccode/";// 保存路径
        if(!file_exists($path))
        {
            mkdir($path, 0700);
        }
        $filename =$id.'_'.$time.'.png';// 文件名
        $save_file = $path.$filename;// 保存:png第二个参数
        if(!file_exists($save_file))
        {
            vendor('phpqrcode.phpqrcode');
            $QRcode = new \QRcode();
            $data = url('index/product/detail','id='.$id,'html',true);// 二维码保存的信息
            $level = 'M';// 纠错级别：L、M、Q、H
            $point_size = 7;// 点的大小：1到10,用于手机端4就可以了
            $size = 2;// 空白大小
            ob_end_clean();//清空缓冲区
            $QRcode->png($data, $save_file, $level, $point_size, $size);
        }
        $this->assign('save_file',$save_file);
        return $this->fetch('producCode');
    }
}
