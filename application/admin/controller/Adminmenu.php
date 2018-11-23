<?php

namespace app\admin\controller;

use think\Request;
//extends Common
class AdminMenu extends Common
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
    	
    	$menu = model('Adminmenu');
    	
    	$data=array();
    	
		//搜索
		if(!empty($name)){
			$ndata=array();
            $ndata['name']=['like','%'.$name.'%'];//加搜索条件
            $topAry=array();
            $menuList=$menu->where($ndata)->order('order', 'desc')->select();
            foreach($menuList as $key=>$value)
            {
            	if($value['parent_id']>0) {
					$topAry[$key]=$value['parent_id'];
				}else{
					$topAry[$key]=$value['id'];
				}
            }
            $data['id']=['in',$topAry];
		//	return json_encode($menuList);
			
		}
		
			$menuList=$menu->where($data)->where('parent_id',0)->order('order', 'desc')->paginate(config('paginate.list_rows'));
			foreach($menuList as $k=>$v) {
				$v['sendList']=$menu->where('parent_id',$v['id'])->order('order', 'desc')->select();
			}
    	$this->assign('menuList',$menuList);
        return $this->fetch();    
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
    	$menu = model('Adminmenu');
    	
    	$request = request();
    	$id=$request->param('id');
    	
    	//顶级栏目
    	$menuList=$menu->where('parent_id', '0')->order('id', 'desc')->select();
    	$this->assign('menuList',$menuList);
    	
    	$data=array();
    	!empty($id) && $data=$menu::get($id);
    	
    	$this->assign('data',$data);
    	
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
        $menu = model('Adminmenu');
		
		if($request->method()=='POST') {
			//数据获取
			$data=array(
				'name'=>$request->param('name'),
				'group'=>$request->param('group'),
				'module'=>$request->param('module'),
				'action'=>$request->param('action'),
				'remark'=>$request->param('remark'),
				'status'=>$request->param('status'),
				'order'=>$request->param('order'),
				'id'=>$request->param('id'),
				'parent_id'=>$request->param('parentid'),
			);
			//数据校验
			$validate = validate('Adminmenu');
			
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
				
				 
				$this->success('操作成功', '/admin/Adminmenu/index/');
				 
			}
	
		}
			
		return $this->fetch(); 
    }

    
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
    	$menu=model('Adminmenu');
    	$request = request();
    	
    	if($request->method()=='GET') {
    			
    		$id=$request->param('id');
    		
    		//校验下是否有子菜单，如果有不能直接删除
			$count=$menu->where('parent_id',$id)->count();
			if($count>0) {
				$this->success('请先删除子分类后才能删除顶级分类，请重试');
			}
    		
    		$result=0;
    		$result=$menu->destroy($id);
    			
    		if($result==0){
    			$this->success('操作失败，请重试');
    		} else {
    			$this->success('操作成功', '/admin/Adminmenu/index/');
    		}
    	}
    	
    	$this->success('非法操作，请重试');
    }
}
