<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Agentstock extends Common
{

	//代理商库存管理
	public function index()
	{
		$request = request();

		$name=$agent_id=$contact=0;


		$request->param('name') && $name=$request->param('name');
		$request->param('agent_id') && $agent_id=$request->param('agent_id');
		$request->param('contact') && $contact=$request->param('contact');

		$role=$request->param('role');
	 	$role = !isset($role) ? -1 : $role;


		$data=array();
		$data['is_del'] = 0;
		!empty($name) && $data['name']=['like','%'.$name.'%'];
		!empty($agent_id) && $data['agent_id']=$agent_id;
		$role>=0 && $data['role']=$role;
		!empty($contact) && $data['phone|wechat']=$contact;


		//获取数据列表
		$agent=model('Agents');
		$agentList=$agent->where($data)->order('agent_id', 'desc')->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
		//获取等级最低的
		$agency=model('Agentrewardagency');

		foreach($agentList as $k=>&$v)
		{
			$tmp=$agency->where(['role'=>$v['role']])->find();
			$v['lowestStock']=$tmp['lowest_limit'];
		}
		$this->assign('agentList',$agentList);

		//角色等级列表
		$roleList=model('Agentlevel')->gerRoleList();
 		$this->assign('roleList',$roleList);

 		//搜索条件赋值
 		$this->assign('name',$name);
 		$this->assign('agent_id',$agent_id);
 		$this->assign('role',$role);
 		$this->assign('contact',$contact);

		return $this->fetch();
	}

	//导出
	public function excelIndex()
	{
		//变量获取
		$request = request();
		$name=$request->param('name');
		$agent_id=$request->param('agent_id');
		$contact=$request->param('contact');
		$role=$request->param('role');

		//搜索条件获取
		$data=array();
		!empty($name) && $data['name']=['like','%'.$name.'%'];
		!empty($agent_id) && $data['agent_id']=$agent_id;
		!empty($role) && $data['role']=$role;
		!empty($contact) && $data['phone|wechat']=$contact;

		//获取数据列表
		$agent=model('Agents');
		$agentList=$agent->where($data)->order('agent_id', 'desc')->select();
		//获取等级最低的库存
		$agency=model('Agentrewardagency');

		foreach($agentList as $k=>&$v)
		{
			$tmp=$agency->where(['role'=>$v['role']])->find();
			$v['lowestStock']=$tmp['lowest_limit'];
		}

		//开始导出相关
		$title=array('代理商ID','姓名','联系方式','角色','最低库存金额','当前库存余额');
		$filename='代理商库存记录';

		//循环输出
		$data=array();
		foreach($agentList as $k=>$v)
		{
			$data[$k]['agent_id']=$v->agent_id;
			$data[$k]['name']=$v->name;
			$data[$k]['contact']='手机号:'.$v->phone." 微信号:".$v->wechat;
			$data[$k]['rolename']=get_reward_levelname($v->role);

			$data[$k]['lowestStock']=$v['lowestStock'];
			$data[$k]['stock_money']=$v['stock_money'];

		}

		$this->exportexcel($data,$title,$filename);
	}

	//充值库存余额
	public function charge($id)
	{
		//获取信息
		$agent=model('Agents');
		$agentInfo=$agent->find($id);
		$this->assign('agentInfo',$agentInfo);

		return $this->fetch();
	}

	//充值保存
	public function chargeSave()
	{
		$request=request();
		$agent=model('Agents');
		$charge=model('Agentstockchange');


		$data=array();
		$data['agent_id']=$request->param('agent_id');
		$data['money']=$request->param('money');
		$data['status']=2;//已审核
		$data['account_type']=4;//后台充值类型
		$data['create_time']=time();
		$data['change_type']=2;//后台充值
		$data['audit_time']=time();
		$data['auditor_id']=session('userid');
		$data['remark']='管理员'.session('username').'后台手工充值';

		$agentInfo=$agent->find($data['agent_id']);

		$data['change_before']=$agentInfo['stock_money'];
		$data['change_after']=$agentInfo['stock_money']+$data['money'];

		//数据校验
		$validate = validate('Agentstockchange');

		if(!$validate->check($data)){
			$this->error($validate->getError());
		} else {

			$result=$resultUpdate=0;

			//插入一条充值记录
			$result=$charge->save($data);
			$chargeId=$charge->id;
			
			//用户的账号余额同时变更
			$resultUpdate=$agentInfo->setInc('stock_money',$data['money']);
			
			//充值库存，上级产生收益
			if($chargeId) {
				agent_charge_stock_provide_profit($chargeId);
				agent_reward_recommend_no_ordernumber($chargeId,2);
			}
			
			if($result && $resultUpdate) {
				$this->success('操作成功', '/admin/Agentstock/index/');
			} else {
				$this->error('数据出错，请检查');
			}

		}

	}

	//充值记录
	public function chargeList()
	{
		$charge=model('Agentstockchange');
		$request=request();


		$status=$agent_id=$name=$contact=$role=$audit_stime=$audit_etime=$apply_stime=$apply_etime=0;

		$request->param('status') && $status=$request->param('status');
		$request->param('agent_id') && $agent_id=$request->param('agent_id');
		$request->param('name') && $name=$request->param('name');
		$request->param('contact') && $contact=$request->param('contact');
		$request->param('role') && $role=$request->param('role');

		$request->param('audit_stime') && $audit_stime=$request->param('audit_stime');
		$request->param('audit_etime') && $audit_etime=$request->param('audit_etime');

		$request->param('apply_stime') && $apply_stime=$request->param('apply_stime');
		$request->param('apply_etime') && $apply_etime=$request->param('apply_etime');

		$data=$ndata=array();

		!empty($agent_id) && $ndata['agents.agent_id']=$agent_id;
		!empty($name) && $ndata['agents.name']=$name;
		!empty($contact) && $ndata['agents.phone|agents.wechat']=$contact;
		!empty($role) && $ndata['agents.role']=$role;

		!empty($status) && $data['agent_stock_change.status']=$status;

		$audit_stime>0 && $data['audit_time']=['>=',strtotime($audit_stime)];
		if($audit_etime>0){
			$tmp_etime=strtotime($audit_etime)+60*60*24;
			$data['audit_time']=['<=',$tmp_etime];
		}

		if(!empty($audit_stime) && !empty($audit_etime)) {
			$tmp_etime=strtotime($audit_etime)+60*60*24;
			$data['audit_time']=['between time',[$audit_stime,$tmp_etime]];
		}

		$apply_stime>0 && $data['create_time']=['>=',strtotime($apply_stime)];
		if($apply_etime>0){
			$tmp_etime=strtotime($apply_etime)+60*60*24;
			$data['create_time']=['<=',$tmp_etime];
		}

		if(!empty($apply_stime) && !empty($apply_etime)) {
			$tmp_etime=strtotime($apply_etime)+60*60*24;
			$data['create_time']=['between time',[$apply_stime,$tmp_etime]];
		}

		$chargeList=$charge->hasWhere('agents',$ndata)->where('change_type in (1,2)')
					->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'));

		//获取用户等级数据
		$level=model('Agentlevel');
		foreach($chargeList as $k=>&$v)
		{
			$tmp=$level->find($v->agents->role);
			$v['rolename']=$tmp['name'];
		}

		$this->assign('chargeList',$chargeList);

		//充值状态
		$chargeStatus=$charge->status;
		$this->assign('chargeStatus',$chargeStatus);

		//角色等级列表
		$level=model('Agentlevel');
		$roleList=$level->order('id', 'desc')->select();
		$this->assign('roleList',$roleList);

		//搜索条件赋值

		$this->assign('status',$status);
		$this->assign('agent_id',$agent_id);
		$this->assign('name',$name);
		$this->assign('contact',$contact);

		$this->assign('role',$role);
		$this->assign('audit_stime',$audit_stime);
		$this->assign('audit_etime',$audit_etime);
		$this->assign('apply_stime',$apply_stime);
		$this->assign('apply_etime',$apply_etime);


		return $this->fetch('chargeList');
	}

	//选中的内容进行导出
	public function excelChargeList()
	{
		//$data=array(),$title=array(),$filename
		$title=array('代理商ID','姓名','微信号','手机号','角色','审核状态','充值金额','账户类型','申请日期','审核日期','备注');
		$filename='充值记录';

		$charge=model('Agentstockchange');
		$request=request();

		$status=$request->param('status');
		$agent_id=$request->param('agent_id');
		$name=$request->param('name');
		$contact=$request->param('contact');
		$role=$request->param('role');

		$audit_stime=$request->param('audit_stime');
		$audit_etime=$request->param('audit_etime');

		$apply_stime=$request->param('apply_stime');
		$apply_etime=$request->param('apply_etime');

		$data=$ndata=array();

		!empty($agent_id) && $ndata['agents.agent_id']=$agent_id;
		!empty($name) && $ndata['agents.name']=$name;
		!empty($contact) && $ndata['agents.phone|agents.wechat']=$contact;
		!empty($role) && $ndata['agents.role']=$role;

		!empty($status) && $data['agent_stock_change.status']=$status;

		$audit_stime>0 && $data['audit_time']=['>=',strtotime($audit_stime)];
		if($audit_etime>0){
			$tmp_etime=strtotime($audit_etime)+60*60*24;
			$data['audit_time']=['<=',$tmp_etime];
		}

		if(!empty($audit_stime) && !empty($audit_etime)) {
			$tmp_etime=strtotime($audit_etime)+60*60*24;
			$data['audit_time']=['between time',[$audit_stime,$tmp_etime]];
		}

		$apply_stime>0 && $data['create_time']=['>=',strtotime($apply_stime)];
		if($apply_etime>0){
			$tmp_etime=strtotime($apply_etime)+60*60*24;
			$data['create_time']=['<=',$tmp_etime];
		}

		if(!empty($apply_stime) && !empty($apply_etime)) {
			$tmp_etime=strtotime($apply_etime)+60*60*24;
			$data['create_time']=['between time',[$apply_stime,$tmp_etime]];
		}


		$chargeList=$charge->hasWhere('agents',$ndata)->where('change_type in (1,2)')
					->where($data)->order('id', 'desc')->select();

		//循环输出
		$data=array();
		foreach($chargeList as $k=>$v)
		{
			$data[$k]['agent_id']=$v->agents->agent_id;
			$data[$k]['name']=$v->agents->name;
			$data[$k]['wechat']=$v->agents->wechat;
			$data[$k]['phone']=$v->agents->phone;
			$data[$k]['rolename']=get_reward_levelname($v->agents->role);
			$data[$k]['status']=$v['status'];
			$data[$k]['money']=$v['money'];
			$data[$k]['account_type']=$v['account_type'];
			$data[$k]['create_time']=$v['create_time'];
			$data[$k]['audit_time']=date('Y-m-d H:i:s',$v['audit_time']);
			$data[$k]['remark']=$v['remark'];
		}

		$this->exportexcel($data,$title,$filename);

	}

	//充值审核
	public function auditCharge()
	{
		$res=0;

		$request=request();
		$charge=model('Agentstockchange');
		$agent=model('Agents');

		$status=$request->param('status');
		$id=$request->param('id');

		//如果审核记录和审核动作都存在
		if($status && $id){

			$chargeInfo=$charge->find($id);

			//如果拒绝，那么设置一下审核记录的动作信息
			if($status=='refuse' && !empty($chargeInfo))
			{
				$data=array();
				$data['audit_time']=time();
				$data['status']=3;
				$data['auditor_id']=session('userid');
				$data['remark']='管理员'.session('username').'拒绝了充值';
				$res=$chargeInfo->save($data);

			//如果同意了
			} else {

				$data=array();
				$data['audit_time']=time();
				$data['status']=2;
				$data['auditor_id']=session('userid');
				$data['remark']='管理员'.session('username').'同意了充值';

				//获取用户的信息
				$agentInfo=$agent->find($chargeInfo['agent_id']);
				$data['change_after'] = $agentInfo->stock_money + $chargeInfo['money'];// CYL变动金额,仅限显示用,实际计算以代理商表库存字段为准
				$result=$chargeInfo->save($data);

				//用户的账号余额同时变更
				 !empty($agentInfo)&& $resultUpdate=$agentInfo->setInc('stock_money',$chargeInfo['money']);

				 if($result && $resultUpdate) {
				 	$res=1;
				 }

			}

		}

		return $res;

	}
	
	//库存低于最低库存记录
	public function lowerStockMoneyLog()
	{
		$request = request();
		
		$name=$agent_id=$contact=$status=0;
		
		
		$request->param('name') && $name=$request->param('name');
		$request->param('agent_id') && $agent_id=$request->param('agent_id');
		$request->param('contact') && $contact=$request->param('contact');
		$request->param('status') && $status=$request->param('status');
		
		$role=$request->param('role');
		$role = !isset($role) ? -1 : $role;
		
		
		$data=array();
		$data['is_del'] = 0;
		!empty($name) && $data['name']=['like','%'.$name.'%'];
		!empty($agent_id) && $data['agent_id']=$agent_id;
		$role>=0 && $data['role']=$role;
		!empty($contact) && $data['phone|wechat']=$contact;
		
		$ndata=array();
		!empty($status) && $ndata['Agentlowerstocklog.status']=$status;
		
		$stockLog=model('Agentlowerstocklog');
		$stockLogList=$stockLog->hasWhere('Agents',$data)->where($ndata)->order('id desc')
					->paginate(config('paginate.list_rows'),false,['query'=>$request->param()]);
		
		//获取等级最低的
		$agency=model('Agentrewardagency');
		
		foreach($stockLogList as $k=>$v)
		{
			$tmp=$agency->where(['role'=>$v['role']])->find();
			$v['lowestStock']=$tmp['lowest_limit'];
		}
		$this->assign('stockLogList',$stockLogList);
		
		$statusList=$stockLog->status;
		$this->assign('statusList',$statusList);
		
		//角色等级列表
		$roleList=model('Agentlevel')->gerRoleList();
		$this->assign('roleList',$roleList);
		
		
		//搜索条件赋值
		$this->assign('name',$name);
		$this->assign('agent_id',$agent_id);
		$this->assign('role',$role);
		$this->assign('contact',$contact);
		$this->assign('status',$status);
		
		return $this->fetch();
	}

	//导出库存降级记录
	public function excelLowerStockMoneyLog()
	{
		$request = request();
		
		$name=$agent_id=$contact=$status=0;
		
		
		$request->param('name') && $name=$request->param('name');
		$request->param('agent_id') && $agent_id=$request->param('agent_id');
		$request->param('contact') && $contact=$request->param('contact');
		$request->param('status') && $status=$request->param('status');
		
		$role=$request->param('role');
		$role = !isset($role) ? -1 : $role;
		
		
		$data=array();
		$data['is_del'] = 0;
		!empty($name) && $data['name']=['like','%'.$name.'%'];
		!empty($agent_id) && $data['agent_id']=$agent_id;
		$role>=0 && $data['role']=$role;
		!empty($contact) && $data['phone|wechat']=$contact;
		
		$ndata=array();
		!empty($status) && $ndata['Agentlowerstocklog.status']=$status;
		
		$stockLog=model('Agentlowerstocklog');
		$stockLogList=$stockLog->hasWhere('Agents',$data)->where($ndata)->order('id desc')->select();
		 
		//开始导出相关
		$title=array('代理商ID','姓名','联系方式','当前角色','创建时间','降级时间','降级前角色','最低库存金额','当前库存余额','状态');
		$filename='代理商降级库存记录';

		//获取等级最低的
		$agency=model('Agentrewardagency');
		
		//循环输出
		$data=array();
		foreach($stockLogList as $k=>$v)
		{
			$data[$k]['agent_id']=$v->agents->agent_id;
			$data[$k]['name']=$v->agents->name;
			$data[$k]['contact']='手机号:'.$v->agents->phone." 微信号:".$v->agents->wechat;
			$data[$k]['rolename']=get_reward_levelname($v->agents->role);
			 
			$data[$k]['create_time']=$v->create_time;
			$data[$k]['down_time']=date('Y-m-d H:i:s',$v->down_time);
			$data[$k]['role']=get_reward_levelname($v->role);
			
			$tmp=$agency->where(['role'=>$v['role']])->find();
			$data[$k]['lowestStock']=$tmp['lowest_limit'];
			$data[$k]['stock_money']=$v->agents->stock_money;
			$data[$k]['status']=$v['status'];
			 
		}
		 
		$this->exportexcel($data,$title,$filename);
	}
}
