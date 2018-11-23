<?php

namespace app\admin\controller;

use think\Request;

class AdminUser extends Common
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$request = request();
    	$username=$request->param('username');
    	
    	$data=array();
    	!empty($username) && $data['username']=['like','%'.$username.'%']; 
    	
        $user = model('Adminuser');
    	$userList=$user->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'));
    	$this->assign('userList',$userList);
        return $this->fetch(); 
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
    	$user = model('Adminuser');
    	
    	$request = request();
    	$id=$request->param('id');
    	
    	$data=array();
    	!empty($id) && $data=$user::get($id);
    	$this->assign('data',$data);
    	
    	//获取用户组
    	$groupList=model('Admingroup')->order('id', 'desc')->select();
    	$this->assign('groupList',$groupList);
    	
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
    	$user = model('Adminuser');
		
		if($request->method()=='POST') {
			//数据获取
			$data=array(
				'username'=>$request->param('username'),
				'realname'=>$request->param('realname'),
				'email'=>$request->param('email'),
				'group'=>$request->param('group'),
				'id'=>$request->param('id'),
				'status'=>$request->param('status'),
			);
			
			$request->param('password') && $data['password']=$request->param('password');
			
			//数据校验
			$validate = validate('Adminuser');
			
			//添加和编辑验证的场景不同
			if($data['id']) {
				$res=$validate->scene('edit')->check($data);
			} else {
				$res=$validate->check($data);
			}
	 
			//校验完原始的数据之后在进行md5	
			isset($data['password']) && $data['password']=md5($request->param('password'));
			
			if(!$res){
				$this->error($validate->getError());
			} else {
				 
				$result=0;
				if(empty($data['id'])){//添加
					$data['create_time']=time();
					$data['count']=0;
					$result=$user->save($data);
				} else {
					$result=$user->save($data,array('id'=>$data['id']));//更新
				}
				 
				$this->success('操作成功', '/admin/Adminuser/index/');
				 
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
        $user=model('Adminuser');
    	$request = request();
    	
    	if($request->method()=='GET') {
    			
    		$id=$request->param('id');
    		
    		$result=0;
    		$result=$user->destroy($id);
    			
    		if($result==0){
    			$this->success('操作失败，请重试');
    		} else {
    			$this->success('操作成功', '/admin/Adminuser/index/');
    		}
    	}
    	
    	$this->success('非法操作，请重试');
    }
}
