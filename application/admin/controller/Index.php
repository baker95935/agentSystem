<?php
namespace app\admin\controller;
use think\Session;

class Index extends Common
{
	public function index()         
	{              
	 
		//根据用户组 获取权限
		$group=Session::get('group');
		$admingroup=model('Admingroup');
		$dataInfo=$admingroup->get($group);
		
		//判断用户组的权限 如果是超级管理员
		$rights=model('Admingroup')->rights($group);
		
		//获取顶级栏目显示
		$searchwhere=[];
		$dataInfo['super']==2 && $searchwhere['id']=['in',$dataInfo['type']];
		$menuList=model('Adminmenu')->where($searchwhere)->where('status', '1')->where('parent_id',0)->order('order', 'desc')->select();
	 
		$this->assign('rights',$rights);    
		$this->assign('menuList',$menuList);
		return $this->fetch();    
	}  

	//登录成功后的首页
	public function home()
	{
		//加载各种模型
		
		$rank=model('Agentachievementtop');
		
		//获取年和月
		$month=date('m');
		$year=date('Y');
		$day=date('d');
		$yesterday=date('d',strtotime('-1 day'));
		$this->assign('year',$year);
		$this->assign('month',$month);
		$this->assign('day',$day);
		$this->assign('yesterday',$yesterday);

		
		//获取各等级的总数
		$levelInfo=$rank->getLevelInfo();
		$this->assign('levelInfo',$levelInfo);
		 
		
		//1申请注册2申请升级3日增代理商 
		$agents=$rank->getAgentsInfo($day,$month);
		$this->assign('agents',$agents);
		
		//出售中的商品数
		//库存中的商品数
		$products=$rank->getProdctInfo();
		$this->assign('products',$products);
		
		//数据统计,总订单相关
		$totals=$rank->getTotalOrders($month);
		$this->assign('totals',$totals);
		
		//最近7天的订单数和金额
		$sevens=$rank->getSevenInfo();
		$this->assign('sevens',$sevens);
		
		
		//总奖励
		$totalReward=$rank->getTotalRewardInfo();
		$this->assign('totalReward',$totalReward);
		
		//上月销售排行
		$orderAmountList=$rank->getOrderAmountTop($month,$year);
		$this->assign('orderAmountList',$orderAmountList);
		
		//上月推荐排行
		$recommendList=$rank->getRecommendTop($month,$year);
		$this->assign('recommendList',$recommendList);
		
		//上月总奖励排行榜-总收益表
		$rankProfitList=$rank->getProfitReward($month,$year);
		$this->assign('rankProfitList',$rankProfitList);
		
		//上月绩效排行榜-绩效奖励表 
		$rankPerformanceList=$rank->getPerformanceReward($month,$year);
		$this->assign('rankPerformanceList',$rankPerformanceList);
		
		
		return $this->fetch();
	}
		  
}                   