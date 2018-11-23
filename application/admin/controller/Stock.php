<?php
namespace app\Admin\controller;

use think\Controller;
use think\Request;
use think\Session;
class Stock extends Controller
{

	/**
	 * 代理商库存
	 */
	public function index()
	{
		$m_agents = model('Agents');
		$m_agent_level = model('Agentlevel');
		$role_lang = $m_agent_level->getRoleName();
        $param = request();
        $where = ['is_del'=>0];// 查询条件
		$list = $m_agents->alias('a')->field('a.agent_id,a.nickname,a.name,a.phone,a.wechat,a.generation,a.role,a.stock_money,r.lowest_limit')->join('agent_reward_agency r','a.role=r.role','LEFT')->where($where)->order('a.agent_id asc')->paginate(10);
		foreach ($list as $listk => $listv)
		{
			$listv['role_lang'] = $m_agent_level->getNameById($listv['role']);
		}
		$this->assign('list',$list);
		$this->assign('role_lang',$role_lang);
		return $this->fetch();
	}
}