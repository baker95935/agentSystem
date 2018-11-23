<?php

namespace app\admin\controller;

 
use think\Request;

class AdminGroup extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$request = request();
    	$name=$request->param('name');
    	
    	$data=array();
    	!empty($name) && $data['name']=['like','%'.$name.'%'];
    	
    	$group = model('Admingroup');
    	$groupList=$group->where($data)->order('id', 'desc')->select();
    	$this->assign('groupList',$groupList);
    	
        return $this->fetch(); 
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
    	$group = model('Admingroup');
    	 
    	$request = request();
    	$id=$request->param('id');
    	 
    	$data=array();
    	!empty($id) && $data=$group::get($id);
    	//权限列表搞成数组容易实现
    	if($data && $data['super']==2) {
    		!empty($data['type']) && $data['typeAry']=explode(',',$data['type']);
    		!empty($data['node']) && $data['nodeAry']=explode(',',$data['node']);
    	}
    	$this->assign('data',$data);
    	
    	//设置默认显示的权限
    	$defaultRight=1;
    	!empty($data) && $defaultRight=$data['super'];
    	$this->assign('defaultRight',$defaultRight);
    	
    	//所有的菜单列表
    	$menu = model('Adminmenu');
    	$menuList=$menu->where('status', '1')->where('parent_id',0)->order('id', 'desc')->select();
    	foreach($menuList as $k=>&$v) {
    		$v['secondList']=$menu->where('status', '1')->where('parent_id',$v['id'])->order('id', 'desc')->select();
    	}
    	$this->assign('menuList',$menuList);
    	 
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
     	$menu = model('Admingroup');
		
		if($request->method()=='POST') {
			
			//数据获取
			$data=array(
				'name'=>$request->param('name'),
				'remark'=>$request->param('remark'),
				'id'=>$request->param('id'),
				'super'=>$request->param('super'),
			);
			
			//如果不是超级权限获取列表
			if($data['super']!=1) {
				$vars=$request->param();
				isset($vars['type']) && $type=$vars['type'];
				isset($vars['node']) && $node=$vars['node'];
					
				//数组变成字符串
				if(!empty($type)){
					$typeStr=implode(",",$type);
					$data['type']=$typeStr;
				} 
				
				if(!empty($node)){
					$nodeStr=implode(",",$node);
					$data['node']=$nodeStr;
				} 

			}
			
			//数据校验
			$validate = validate('Admingroup');
			
			if(!$validate->check($data)){
				$this->error($validate->getError());
			
			} else {
				 
				$result=0;
				if(empty($data['id'])){//添加
					$data['create_time']=time();
					$result=$menu->save($data);
				} else {
					$result=$menu->save($data,array('id'=>$data['id']));//更新
				}
				
				$this->success('操作成功', '/admin/Admingroup/index/');
				 
			}
	
		}
    }


    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $group=model('Admingroup');
    	$request = request();
    	
    	if($request->method()=='GET') {
    			
    		$id=$request->param('id');
    		
    		//校验下请先组中的所有成员才能删除组，请重试
    		$user=model('Adminuser');
			$count=$user->where('group',$id)->count();
	 
			if($count>0) {
				$this->success('请先组中的所有成员才能删除组，请重试');
			}
    		
    		$result=0;
    		$result=$group->destroy($id);
    			
    		if($result==0){
    			$this->success('操作失败，请重试');
    		} else {
    			$this->success('操作成功', '/admin/Admingroup/index/');
    		}
    	}
    	
    	$this->success('非法操作，请重试');
    }
}
