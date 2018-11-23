<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentlevel extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
 		$list=$listLevel=array();
 		
 		$listLevel=array(6,5,4,3,2,1);//初始化等级数量
 		$listLevelCount=count($listLevel);//等级层数
 		
 		//获取等级数据列表,如果没有数据，那么等级开始ID=1
 		$level=model('Agentlevel');
 		$list=$level->order('id', 'desc')->select();
 		$listMinNumber=1;//默认ID为1
 		!empty($list) && $listMinNumber=$level->min('id');
 		!empty($list) && $listLevelCount=$level->count('id');
 
 		
 		$this->assign('listLevelCount',$listLevelCount);
 		$this->assign('listLevel',$listLevel);
 		$this->assign('list',$list);
 		$this->assign('listMinNumber',$listMinNumber);
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
    	$data=array();
    	$result=0;
    	
    	$vars=$request->param();
   
    	$listLevelCount=$vars['listLevelCount'];//等级层数
    	$listMinNumber=$vars['listMinNumber'];//等级主键ID最小值
    	
    	//数据获取
    	for($i=$listMinNumber;$i<$listMinNumber+$listLevelCount;$i++)
    	{
    		$data[$i]['name']=$vars['name'.$i];
    		
    		$data[$i]['deep']=2;
    		$vars['deep'.$i]>0 && $data[$i]['deep']=$vars['deep'.$i];

    		$tmpShow=$tmpValid=1;
    		
    		//radio数据获取
    		isset($vars['show'.$i]) && $tmpShow=$vars['show'.$i];
    		if($tmpShow=='on') {
    			$data[$i]['show']=1;
    		} else {
    			$data[$i]['show']=2;
    		}
    		//radio数据获取
    		isset($vars['valid'.$i]) && $tmpValid=$vars['valid'.$i];
    		if($tmpValid=='on') {
    			$data[$i]['valid']=1;
    		} else {
    			//如果是设置不启用，那么删除相关的奖励
    			$this->delRewardInfoByLevelId($i);
    			$data[$i]['valid']=2;
    		}
    		
    		isset($vars['id'.$i]) && $data[$i]['id']=$vars['id'.$i];
    	}
    	
    	$level=model('Agentlevel');
    	
    	//数据校验
    	$validate = validate('Agentlevel');
    		
		foreach($data as $k=>$v) {
			if(!$validate->check($v)){
    			$this->error($validate->getError());
    		}		
    	}
    	

    	$result=$level->saveAll($data);
 
	    if($result) {
	    	$this->success('操作成功', '/admin/Agentlevel/index/');
	    } else {
	    	$this->success('操作失败或未生效请重试', '/admin/Agentlevel/index/');
	    }
    	
    }
    
    //根据奖励等级删除相关表中的参数
    private  function delRewardInfoByLevelId($id)
    {
    	//招商，代理，绩效,
    	model('Agentrewardrecommend')->destroy(['role'=>$id]);
    	model('Agentrewardagency')->destroy(['role'=>$id]);
    	model('Agentrewardperformance')->destroy(['role'=>$id]);
    	
    	//产品推荐奖励表，产品代理奖励表
    	model('Productrecommendreward')->destroy(['role'=>$id]);
    	model('Productagentreward')->destroy(['role'=>$id]);
    	
    }
    
}
