<?php

namespace app\admin\model;

use think\Model;

class Admingroup extends Model
{
    protected $table = 'admin_group';
    
    //获取组权限
    public function rights($group)
    {
    	$rights='';
    
    	if(!empty($group))
    	{
    		$dataInfo=Admingroup::get($group);
    		//组建权限
    		$rights='[';
    		$menu = model('Adminmenu');
    		
    		
    		if($dataInfo['super']==1)
    		{
    			//寻找后台登录首页
    			$homePage='';
    			$info=$menu->where("module='Index' and action='home' and parent_id>0")->find();
    			$info['id'] &&	$indexHomePage=",homePage:'".$info['id']."'";
    			 
    			
    			//代理商角色设置
    			$info=$menu->where("module='Agentlevel' and action='index' and parent_id>0")->find();
    			$info['id'] && $rHomePage=",homePage:'".$info['id']."'";
    			 
    			 
    			//产品清单
    			$info=$menu->where("module='Productmanagement' and action='index' and parent_id>0")->find();
    			$info['id'] && $pHomePage=",homePage:'".$info['id']."'";
    			 
    			//订单管理
    			$info=$menu->where("module='Agentorders' and action='index' and parent_id>0")->find();
    			$info['id'] &&	$oHomePage=",homePage:'".$info['id']."'";
    			 
    			//代理商管理
    			$info=$menu->where("module='Agents' and action='agentsList' and parent_id>0")->find();
    			$info['id'] &&	$aHomePage=",homePage:'".$info['id']."'";
    			 
    			//代理商库存
    			$info=$menu->where("module='Agentstock' and action='index' and parent_id>0")->find();
    			$info['id'] && $cHomePage=",homePage:'".$info['id']."'";
    			
    			//礼包订单
    			$info=$menu->where("module='Promotiongiftorder' and action='index' and parent_id>0")->find();
    			$info['id'] &&	$gHomePage=",homePage:'".$info['id']."'";
    			
    			//数据统计
    			$info=$menu->where("module='Withdrawal' and action='history' and parent_id>0")->find();
    			$info['id'] &&	$dHomePage=",homePage:'".$info['id']."'";
    			
    			 
    			//获取菜单列表
		    	$menuList=$menu->where('status', '1')->where('parent_id',0)->order('order', 'desc')->select();
		    	foreach($menuList as $k=>$v) {
		    		$homePage=0;
		    		
		    		if($v['module']=='Index' && $v['action']=='index') {
		    			$homePage=$indexHomePage;
		    		}
		    	 
		    		//产品管理
		    		if($v['module']=='Product' && $v['action']=='index') {
		    			$homePage=$pHomePage;
		    		}
		    		
		    		//角色设置
		    		if($v['module']=='Reward' && $v['action']=='index') {
		    			$homePage=$rHomePage;
		    		}
		    		
		    		//订单管理
		    		if($v['module']=='Agentorders' && $v['action']=='Agentorders') {
		    			$homePage=$oHomePage;
		    		}
		    		
		    		//代理商管理
		    		if($v['module']=='Agents' && $v['action']=='agentsList') {
		    			 $homePage=$aHomePage;
		    		}
		    		
		    		//财务管理
		    		if($v['module']=='Agentstock' && $v['action']=='index') {
		    			$homePage=$cHomePage;
		    		}
		    		
		    		//营销活动
		    		if($v['module']=='Event' && $v['action']=='index') {
		    			$homePage=$gHomePage;
		    		}
		    		
		    		//数据统计
		    		if($v['module']=='Data' && $v['action']=='index') {
		    			$homePage=$dHomePage;
		    		}
	 
		    	
		    		if($homePage) {
		    			$rights.="{id:'{$v['id']}'{$homePage},menu:[{text:'".$v['name']."',items:[";
		    		}  else {
		    			$rights.="{id:'{$v['id']}',menu:[{text:'".$v['name']."',items:[";
		    		}
		    		$sendList=$menu->where('status', '1')->where('parent_id',$v['id'])->order('order', 'desc')->select();
		     
		    		foreach($sendList as $kk=>$vv) {
		    			$rights.="
        				 {id:'{$vv['id']}',text:'{$vv['name']}',href:'/{$vv['group']}/{$vv['module']}/{$vv['action']}'},
		    			";
		    		}
		    		$rights.="]}]},";
		    	}
		    	
    		} else {
    			//读取用户组的权限

    			//寻找后台登录首页
    			$homePage='';
    			$info=$menu->where("module='Index' and action='home' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] &&	$indexHomePage=",homePage:'".$info['id']."'";
    			
    			 
    			//代理商角色设置
    			$info=$menu->where("module='Agentlevel' and action='index' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] && $rHomePage=",homePage:'".$info['id']."'";
    			
    			
    			//产品清单
    			$info=$menu->where("module='Productmanagement' and action='index' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] && $pHomePage=",homePage:'".$info['id']."'";
    			
    			//订单管理
    			$info=$menu->where("module='Agentorders' and action='index' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] &&	$oHomePage=",homePage:'".$info['id']."'";
    			
    			//代理商管理
    			$info=$menu->where("module='Agents' and action='agentsList' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] &&	$aHomePage=",homePage:'".$info['id']."'";
    			
    			//代理商库存
    			$info=$menu->where("module='Agentstock' and action='index' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] && $cHomePage=",homePage:'".$info['id']."'";
    			 
    			//礼包订单
    			$info=$menu->where("module='Promotiongiftorder' and action='index' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] &&	$gHomePage=",homePage:'".$info['id']."'";
    			 
    			//数据统计
    			$info=$menu->where("module='Withdrawal' and action='history' and parent_id>0")->where('id','in',$dataInfo['node'])->find();
    			$info['id'] &&	$dHomePage=",homePage:'".$info['id']."'";
    			
    			$menuList=$menu->where('id','in',$dataInfo['type'])->order('order','desc')->select();
    			foreach($menuList as $k=>$v) {
    				$homePage=0;
    				if($v['module']=='Index' && $v['action']=='index') {
    					isset($indexHomePage) && $homePage=$indexHomePage;
    				}
    				
    				//产品管理
    				if($v['module']=='Product' && $v['action']=='index') {
    					isset($pHomePage) && $homePage=$pHomePage;
    				}
    				
    				//角色设置
    				if($v['module']=='Reward' && $v['action']=='index') {
    					isset($rHomePage) && $homePage=$rHomePage;
    				}
    				
    				//订单管理
    				if($v['module']=='Agentorders' && $v['action']=='Agentorders') {
    					isset($oHomePage) && $homePage=$oHomePage;
    				}
    				
    				//代理商管理
    				if($v['module']=='Agents' && $v['action']=='agentsList') {
    					isset($aHomePage) && $homePage=$aHomePage;
    				}
    				
    				//财务管理
    				if($v['module']=='Agentstock' && $v['action']=='index') {
    					isset($cHomePage) && $homePage=$cHomePage;
    				}
    				
    				//营销活动
    				if($v['module']=='Event' && $v['action']=='index') {
    					isset($gHomePage) && $homePage=$gHomePage;
    				}
    				
    				//数据统计
    				if($v['module']=='Data' && $v['action']=='index') {
    					isset($dHomePage) && $homePage=$dHomePage;
    				}
    				
    				 
    				if($homePage) {
    					$rights.="{id:'{$v['id']}'{$homePage},menu:[{text:'".$v['name']."',items:[";
    				}  else {
    					$rights.="{id:'{$v['id']}',menu:[{text:'".$v['name']."',items:[";
    				}
    			
    				$sendList=$menu->where('id','in',$dataInfo['node'])->where('status', '1')->where('parent_id',$v['id'])->order('order', 'desc')->select();
     				
    				foreach($sendList as $kk=>$vv) {
    					$rights.="
    					{id:'{$vv['id']}',text:'{$vv['name']}',href:'/{$vv['group']}/{$vv['module']}/{$vv['action']}'},
    							";
    				}
    			 
    				$rights.="]}]},";
    			}
    			
    		}
    		
    		$rights.=']';//结束标志
    	}
 
    	return $rights;
    
    }
}
