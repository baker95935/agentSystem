<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentchangerole extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $request = request();
		
		$name=$agent_id=$contact=$reason=$type=0;
		
		
		$request->param('name') && $name=$request->param('name');
		$request->param('agent_id') && $agent_id=$request->param('agent_id');
		$request->param('contact') && $contact=$request->param('contact');
		$request->param('reason') && $reason=$request->param('reason');
		$request->param('type') && $type=$request->param('type');
		
		$role=$request->param('role');
		$role = !isset($role) ? -1 : $role;
		
		
		$data=array();
		$data['is_del'] = 0;
		!empty($name) && $data['name']=['like','%'.$name.'%'];
		!empty($agent_id) && $data['agent_id']=$agent_id;
		$role>=0 && $data['role']=$role;
		!empty($contact) && $data['phone|wechat']=$contact;
		
		$ndata=array();
		!empty($reason) && $ndata['Agentchangerole.reason']=$reason;
		!empty($type) && $ndata['Agentchangerole.type']=$type;
		
		$change=model('Agentchangerole');
		$changeList=$change->hasWhere('Agents',$data)->where($ndata)->order('id desc')
					->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
 
		$this->assign('changeList',$changeList);
		
		//来源
		$reasonList=$change->reason;
		$this->assign('reasonList',$reasonList);
		
		//方式
		$typeList=$change->type;
		$this->assign('typeList',$typeList);
		
		//角色等级列表
		$roleList=model('Agentlevel')->gerRoleList();
		$this->assign('roleList',$roleList);
		
		
		//搜索条件赋值
		$this->assign('name',$name);
		$this->assign('agent_id',$agent_id);
		$this->assign('role',$role);
		$this->assign('contact',$contact);
		$this->assign('reason',$reason);
		$this->assign('type',$type);
		
		return $this->fetch();
    }

    //页面导出
    public function excelIndex()
    {
    	$request = request();
    	
    	$name=$agent_id=$contact=$reason=$type=0;
    	
    	
    	$request->param('name') && $name=$request->param('name');
    	$request->param('agent_id') && $agent_id=$request->param('agent_id');
    	$request->param('contact') && $contact=$request->param('contact');
    	$request->param('reason') && $reason=$request->param('reason');
    	$request->param('type') && $type=$request->param('type');
    	
    	$role=$request->param('role');
    	$role = !isset($role) ? -1 : $role;
    	
    	
    	$data=array();
    	$data['is_del'] = 0;
    	!empty($name) && $data['name']=['like','%'.$name.'%'];
    	!empty($agent_id) && $data['agent_id']=$agent_id;
    	$role>=0 && $data['role']=$role;
    	!empty($contact) && $data['phone|wechat']=$contact;
    	
    	$ndata=array();
    	!empty($reason) && $ndata['Agentchangerole.reason']=$reason;
    	!empty($type) && $ndata['Agentchangerole.type']=$type;
    	
    	$change=model('Agentchangerole');
    	$changeList=$change->hasWhere('Agents',$data)->where($ndata)->order('id desc')->select();
    	
    	//开始导出相关
    	$title=array('代理商ID','姓名','联系方式','当前角色','创建时间','变动前角色','变动后角色','来源','方式');
    	$filename='代理商角色变动记录';
    	
    	//循环输出
    	$data=array();
    	foreach($changeList as $k=>$v)
    	{
    		$data[$k]['agent_id']=$v->agents->agent_id;
    		$data[$k]['name']=$v->agents->name;
    		$data[$k]['contact']='手机号:'.$v->agents->phone." 微信号:".$v->agents->wechat;
    		$data[$k]['rolename']=get_reward_levelname($v->agents->role);
    	
    		$data[$k]['create_time']=$v->create_time;
    		$data[$k]['before_role']=get_reward_levelname($v->before_role);
    		$data[$k]['after_role']=get_reward_levelname($v->after_role);
    		$data[$k]['reason']=$v['reason'];
    		$data[$k]['type']=$v['type'];
    	 	
    	}
     
    	$this->exportexcel($data,$title,$filename);
    }
}
