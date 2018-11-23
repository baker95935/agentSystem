<?php
namespace app\admin\controller;

use think\Request;

class Rewardtostock extends Common
{

	/* 转库存记录 */
	public function index()
	{
		$received = request();
		$role_lang = model('Agentlevel')->getRoleName();// 角色
		$search = [
			'a_id'  => $received->param('a_id'),
			'name'  => $received->param('name'),
			'phone' => $received->param('phone'),
			'role'  => $received->param('role'),
			'money' => $received->param('money'),
			'begin' => $received->param('begin'),
			'end'   => $received->param('end'),
		];
		$where = ['p.is_del'=>0,'a.is_del'=>0];
		!empty($search['a_id']) && $where['p.a_id'] = $search['a_id'];
		!empty($search['name']) && $where['a.name'] = ['like','%'.$search['name'].'%'];
		!empty($search['phone']) && $where['a.phone'] = $search['phone'];
		if(isset($search['role']) && in_array($search['role'], [0,1,2,3,4,5,6]))
		{
			$where['a.role'] = $search['role'];
		}
		!empty($search['money']) && $where['p.money'] = $search['money'];
		if(!empty($search['begin']) && !empty($search['end']))
		{
			$where['p.create_time'] = ['between',[strtotime($search['begin']),strtotime($search['end'])+86400]];
		}else{
			!empty($search['begin']) && $where['p.create_time'] = ['gt',strtotime($search['begin'])];
			!empty($search['end']) && $where['p.create_time'] = ['lt',strtotime($search['end'])+86400];
		}
		$list = model('ProfitToStock')->alias('p')->field('p.a_id,p.money,p.create_time,p.profit,p.stock,a.name,a.nickname,a.phone,a.wechat,a.generation,a.role')->join('agents a','p.a_id=a.agent_id')->where($where)->paginate(10);
		$this->assign('role_lang',$role_lang);
		$this->assign('list',$list);
		$this->assign('search',$search);
		return $this->fetch();
	}

	/* 导出 */
	public function export()
	{
		$received = request();
		$search = [
			'a_id'  => $received->param('a_id'),
			'name'  => $received->param('name'),
			'phone' => $received->param('phone'),
			'role'  => $received->param('role'),
			'money' => $received->param('money'),
			'begin' => $received->param('begin'),
			'end'   => $received->param('end'),
		];
        $data_field = array('代理商编号','姓名','微信号','手机号','角色','代理总收益','转库金额','当前库存','转库时间');
        $filename = '代理商收益转库存记录';
        $where = ['p.is_del'=>0,'a.is_del'=>0];
		!empty($search['a_id']) && $where['p.a_id'] = $search['a_id'];
		!empty($search['name']) && $where['a.name'] = ['like','%'.$search['name'].'%'];
		!empty($search['phone']) && $where['a.phone'] = $search['phone'];
		if(isset($search['role']) && in_array($search['role'], [0,1,2,3,4,5,6]))
		{
			$where['a.role'] = $search['role'];
		}
		!empty($search['money']) && $where['p.money'] = $search['money'];
		if(!empty($search['begin']) && !empty($search['end']))
		{
			$where['p.create_time'] = ['between',[strtotime($search['begin']),strtotime($search['end'])+86400]];
		}else{
			!empty($search['begin']) && $where['p.create_time'] = ['gt',strtotime($search['begin'])];
			!empty($search['end']) && $where['p.create_time'] = ['lt',strtotime($search['end'])+86400];
		}
        $data = array();// 输出数据
		$list = model('ProfitToStock')->alias('p')->field('p.a_id,p.money,p.create_time,p.profit,p.stock,a.name,a.nickname,a.phone,a.wechat,a.generation,a.role')->join('agents a','p.a_id=a.agent_id')->where($where)->select();
        foreach($list as $k=>$v)
        {
			$data[$k]['a_id']   = $v['a_id'];
			$data[$k]['name']   = $v['name'];
			$data[$k]['wechat'] = $v['wechat'];
			$data[$k]['phone']  = $v['phone'];
			$data[$k]['role']   = $v['generation'].'代'.get_reward_levelname($v['role']);
			$data[$k]['profit'] = $v['profit'];
			$data[$k]['money']  = $v['money'];
			$data[$k]['stock']  = $v['stock'];
			$data[$k]['time']   = $v['create_time'];
        }
        $this->exportexcel($data,$data_field,$filename);
	}
}