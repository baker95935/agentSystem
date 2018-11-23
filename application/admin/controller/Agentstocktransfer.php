<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentstocktransfer extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
         
    	$request = request();
		
		$name=$agent_id=$contact=$operater_agent_id=$money=0;
		
		
		$request->param('name') && $name=$request->param('name');
		$request->param('agent_id') && $agent_id=$request->param('agent_id');
		$request->param('contact') && $contact=$request->param('contact');
		$request->param('operater_agent_id') && $operater_agent_id=$request->param('operater_agent_id');
		$request->param('money') && $money=$request->param('money'); 
 
		
		
		$data=array();
		$data['is_del'] = 0;
		!empty($name) && $data['name']=['like','%'.$name.'%'];
		!empty($agent_id) && $data['agent_id']=$agent_id;
		!empty($contact) && $data['phone|wechat']=$contact;
		
		$ndata=array();
		!empty($operater_agent_id) && $ndata['Agentstocktransfer.operater_agent_id']=$operater_agent_id;
		!empty($money) && $ndata['Agentstocktransfer.money']=$money;
		
		$change=model('Agentstocktransfer');
		$changeList=$change->hasWhere('Agents',$data)->where($ndata)->order('id desc')
					->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
 
		$this->assign('changeList',$changeList);
 
		//搜索条件赋值
		$this->assign('name',$name);
		$this->assign('agent_id',$agent_id);
 
		$this->assign('contact',$contact);
		$this->assign('money',$money);
		$this->assign('operater_agent_id',$operater_agent_id);
		
		return $this->fetch();
    }
     
    //页面导出
    public function excelIndex()
    {
    	$request = request();
    	
    	$name=$agent_id=$contact=$operater_agent_id=$money=0;
    	
    	
    	$request->param('name') && $name=$request->param('name');
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('operater_agent_id') && $operater_agent_id=$request->param('operater_agent_id');
    	$request->param('money') && $money=$request->param('money');
    	
    	
    	
    	$data=array();
    	$data['is_del'] = 0;
    	!empty($name) && $data['name']=['like','%'.$name.'%'];
    	!empty($agent_id) && $data['agent_id']=$agent_id;
    	!empty($contact) && $data['phone|wechat']=$contact;
    	
    	$ndata=array();
    	!empty($operater_agent_id) && $ndata['Agentstocktransfer.operater_agent_id']=$operater_agent_id;
    	!empty($money) && $ndata['Agentstocktransfer.money']=$money;
    	
    	$change=model('Agentstocktransfer');
    	$changeList=$change->hasWhere('Agents',$data)->where($ndata)->order('id desc')->select();
    	
    	//开始导出相关
    	$title=array('转账人ID','代理商ID','转账金额','姓名','联系方式','当前角色','创建时间');
    	$filename='代理商库存转账记录';
    	
    	//循环输出
    	$data=array();
    	foreach($changeList as $k=>$v)
    	{
    		$data[$k]['operater_agent_id']=$v->operater_agent_id;
    		$data[$k]['agent_id']=$v->agents->agent_id;
    		$data[$k]['money']=$v->money;
    		$data[$k]['name']=$v->agents->name;
    		$data[$k]['contact']='手机号:'.$v->agents->phone." 微信号:".$v->agents->wechat;
    		$data[$k]['rolename']=get_reward_levelname($v->agents->role);
    		 
    		$data[$k]['create_time']=$v->create_time;
    		 
    	}
    	 
    	$this->exportexcel($data,$title,$filename);
    	 
    }
    
}
