<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentreward extends Controller
{
    /**
     * 招商奖励
     *
     * @return \think\Response
     */
    public function recommond()
    {
    	//招商奖励是否开启
    	$config=model('Agentrewardconfig');
    	$configInfo=$config->order('id','desc')->limit(1)->find();
    	$this->assign('configInfo',$configInfo);
    	
    	
    	//招商奖励配置列表
    	$recommendInfo=array();
    	$recommend=model('Agentrewardrecommend');
    	$recommendInfo=$recommend->order('id','desc')->select();
    	$this->assign('recommendInfo',$recommendInfo);
    	
    
	    //等级列表,如果有数据，那么获取
    	$level=model('Agentlevel');
    	$listLevel=$level->where('valid=1')->order('id','desc')->select();
    	
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
 
    	
    	$this->assign('listLevel',$listLevel);
    	
        return $this->fetch(); 
    }


    /**
     * 保存招商奖励
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function recommondSave(Request $request)
    {
        //处理是否开启推荐奖励
        $ndata=array();
    	$ndata['id']=$request->param('cid');
    	
    	$valid_performance_reward=$request->param('valid_recommend_reward');
    	if($valid_performance_reward=='on') {
    		$ndata['valid_recommend_reward']=1;
    	} else {
    		$ndata['valid_recommend_reward']=2;
    	}
    	
    	
    	$config=model('Agentrewardconfig');
    	if($ndata['id']) {//更新
    		$config->save($ndata,array('id'=>$ndata['id']));
    	} else {//插入
    		$config->save($ndata);
    	}
    	
    	
    	//处理到推荐奖励表中的数据 判断是否有记录 没有就插入，有就更新
    	$data=array();
    	
    	//获取等级数据的列表，需要批量获取数据
    	$level=model('Agentlevel');
    	$listLevel=$level->where('valid=1')->order('id','desc')->select();

    	$recommend=model('Agentrewardrecommend');
    	
    	//循环获取数据存储
    	$j=0;
    	foreach($listLevel as $k=>$v) 
    	{
    		for($i=1;$i<$v['deep']+1;$i++) {
    			$data[$j]['role']=$v['id'];
    			$data[$j]['hierarchy']=$i;
    			$data[$j]['value']=$request->param('value_'.$v['id'].'_'.$i);
    			$tmpId=$request->param('id_'.$v['id'].'_'.$i);
    			$tmpId>0 &&  $data[$j]['id']=$tmpId;
    			$j++;
    		}
    	}
 
    	//数据校验
    	$validate = validate('Agentrewardrecommend');
    	
    	foreach($data as $k=>$v) {
    		if(!$validate->check($v)){
    			$this->error($validate->getError());
    		}
    	}
 
    	$result=$recommend->saveAll($data);
     
    	if($result) {
    		$this->success('操作成功', '/admin/Agentreward/recommond/');
    	} else {
    		$this->success('操作失败或未生效请重试', '/admin/Agentreward/agency/');
    	}
	 
	 
 
    }
    
    /**
     * 代理奖励
     *
     * @return \think\Response
     */
    public function agency()
    {
    	//获取奖励配置的信息
    	$config=model('Agentrewardconfig');
    	$configInfo=$config->order('id','desc')->limit(1)->find();
    	$this->assign('configInfo',$configInfo);
    	 
    	 
    	//等级列表,如果有数据，那么获取
    	$level=model('Agentlevel');
    	$listLevel=$level->where('valid=1')->order('id','desc')->select();

    	$agency=model('Agentrewardagency');
    	
    	foreach($listLevel as $k=>&$v) {
    		$agencyTmp=$agency->where(array('role'=>$v['id']))->find();

    		isset($agencyTmp['ratio']) && $v['ratio']=$agencyTmp['ratio'];
    		isset($agencyTmp['pre_deposit']) && $v['pre_deposit']=$agencyTmp['pre_deposit'];
    		isset($agencyTmp['first_goods_number']) && $v['first_goods_number']=$agencyTmp['first_goods_number'];
    		isset($agencyTmp['first_goods_money']) && $v['first_goods_money']=$agencyTmp['first_goods_money'];
    		isset($agencyTmp['lowest_limit']) && $v['lowest_limit']=$agencyTmp['lowest_limit'];
    		isset($agencyTmp['id']) && $v['agency_id']=$agencyTmp['id'];
    	}
    	
    	$this->assign('listLevel',$listLevel);
    	
    	return $this->fetch(); 
    }
    
    
    /**
     * 保存代理奖励
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function agencySave(Request $request)
    {
    	
    	//处理是否开启代理奖励
    	$ndata=array();
    	$valid_agency_reward=$request->param('valid_agency_reward');
    	if($valid_agency_reward=='on') {
    		$ndata['valid_agency_reward']=1;
    	} else {
    		$ndata['valid_agency_reward']=2;
    	}
    	
    	$ndata['id']=$request->param('cid');
    	 
    	$config=model('Agentrewardconfig');
    	if($ndata['id']) {//更新
    		$config->save($ndata,array('id'=>$ndata['id']));
    	} else {//插入
    		$config->save($ndata);
    	}
    	
    	//获取等级数据的列表，需要批量获取数据
    	$level=model('Agentlevel');
    	$listLevel=$level->where('valid=1')->order('id','desc')->select();
    	
    	//代理奖励
    	$data=array();
    	$vars=$request->param();
 
    	//循环获取数据保存
    	$i=0;
    	foreach($listLevel as $k=>&$v)
    	{
    		$data[$i]['role']=$vars['role'.$v['id']];
    		$data[$i]['ratio']=$vars['ratio'.$v['id']];
    		$data[$i]['pre_deposit']=$vars['pre_deposit'.$v['id']];
    		$data[$i]['first_goods_number']=$vars['first_goods_number'.$v['id']];
    		
    		if(isset($vars['first_goods_money'.$v['id']]) && $vars['first_goods_money'.$v['id']]>0){//如果默认没填写，那么计算出来
    			$data[$i]['first_goods_money']=$vars['first_goods_money'.$v['id']];
    		} else {
    			$data[$i]['first_goods_money']=round($data[$i]['pre_deposit']*$data[$i]['first_goods_number']/100,2);
    		}
    		$data[$i]['lowest_limit']=$vars['lowest_limit'.$v['id']];
    		
    		$tmp=$vars['id'.$v['id']];
    		$tmp>0 && $data[$i]['id']=$vars['id'.$v['id']];
    		
    		// //增加校验首次最低拿货金额必须小于库存额度
    		// if($data[$i]['first_goods_money'] > $data[$i]['pre_deposit']) {
    		// 	$this->success('操作失败，首次最低拿货金额必须小于库存额度', '/admin/Agentreward/agency/');
    		// } 
    		
    		$i++;
    	}

    	
    	$agency=model('Agentrewardagency');
    	
    	//数据校验
    	$validate = validate('Agentrewardagency');
    		
		foreach($data as $k=>$v) {
			if(!$validate->check($v)){
    			$this->error($validate->getError());
    		}		
    	}
    	
    	$result=$agency->saveAll($data);
 
	    if($result) {
	    	$this->success('操作成功', '/admin/Agentreward/agency/');
	    } else {
	    	$this->success('操作失败或未生效请重试', '/admin/Agentreward/agency/');
	    }
    	 
    }
    
    
    /**
     * 业绩奖励
     *
     * @return \think\Response
     */
    public function performance()
    {
    	//获取奖励配置的信息
    	$config=model('Agentrewardconfig');
    	$configInfo=$config->order('id','desc')->limit(1)->find();
    	$this->assign('configInfo',$configInfo);
    	
    	
    	//业绩/绩效奖励的信息
    	$performanceInfo=array();
    	$performance=model('Agentrewardperformance');
    	
      	//等级列表,如果有数据，那么获取
    	$level=model('Agentlevel');
    	$listLevel=$level->where('valid=1')->order('id','desc')->select();
 
    	foreach($listLevel as $k=>&$v) {
    		$performanceTmp=$performance->where(array('role'=>$v['id']))->find();
    		isset($performanceTmp['ratio']) && $v['ratio']=$performanceTmp['ratio'];
    		isset($performanceTmp['id']) && $v['performance_id']=$performanceTmp['id'];
    	}
    	 
    	
    	$this->assign('listLevel',$listLevel);
    	
    	
    	return $this->fetch();
    }
    
    
    /**
     * 保存业绩奖励
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function performanceSave(Request $request)
    {
        	
    	//处理是否开启绩效奖励
    	$ndata=array();
    	$ndata['performance_reward_clear_date']=$request->param('performance_reward_clear_date');
    	$ndata['id']=$request->param('cid');
    	
    	$valid_performance_reward=$request->param('valid_performance_reward');
    	if($valid_performance_reward=='on') {
    		$ndata['valid_performance_reward']=1;
    	} else {
    		$ndata['valid_performance_reward']=2;
    	}
    	
    	//数据校验
    	$validate = validate('Agentrewardconfig');
    	
    	if(!$validate->check($ndata)){
    		$this->error($validate->getError());
    	}
    	 
    	$config=model('Agentrewardconfig');
    	if($ndata['id']) {//更新
    		$config->save($ndata,array('id'=>$ndata['id']));
    	} else {//插入
    		$config->save($ndata);
    	}
    	
    	
    	//获取等级数据的列表，需要批量获取数据
    	$level=model('Agentlevel');
    	$listLevel=$level->where('valid=1')->order('id','desc')->select();
    	
    	//业绩/绩效奖励
    	$data=array();
    	$vars=$request->param();
 
    	//循环获取数据保存
    	$i=0;
    	foreach($listLevel as $k=>&$v)
    	{
    		$data[$i]['role']=$vars['role'.$v['id']];
    		$data[$i]['ratio']=$vars['ratio'.$v['id']];
    		
    		$tmp=$vars['id'.$v['id']];
    		$tmp>0 && $data[$i]['id']=$vars['id'.$v['id']];
    		
    		$i++;
    	}
    	 
    	$performance=model('Agentrewardperformance');
    	 
    	//数据校验
    	$validate = validate('Agentrewardperformance');
    	
    	foreach($data as $k=>$v) {
			if(!$validate->check($v)){
    			$this->error($validate->getError());
    		}
    	}
    	 
    	$result=$performance->saveAll($data);
    	
    	if($result) {
    		$this->success('操作成功', '/admin/Agentreward/performance/');
    	} else {
    		$this->success('操作失败或未生效请重试', '/admin/Agentreward/performance/');
    	}
    	
    	
    }

    
}
