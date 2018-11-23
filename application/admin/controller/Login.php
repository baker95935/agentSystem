<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Session;

class Login extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->fetch();
    }


    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function login()
    {
    	$user = model('Adminuser');
		$request=Request();
    	if($request->method()=='POST') {
    		//数据获取
    		$data=array(
    				'username'=>$request->param('username'),
    				'password'=>md5($request->param('password')),
			);
			
    		//数据校验
    		$validate = validate('Login');
    			
    		if(!$validate->check($data)){
    			return	$this->error($validate->getError());
    				
    		} else {
    				
    			$result=$user->validLogin($data);
 
    			if($result) {
					//$this->success('登录成功！', '/admin/Index/index/');
					return json_encode(['code'=>1,'msg'=>'登录成功！','url'=>url('/admin/Index/index/')]);
				
    			} else {
					//$this->success('登录失败，用户名或者密码错！', '/admin/Login/index/');
					return json_encode(['code'=>-1,'msg'=>'登录失败，用户名或者密码错!']);	
    			}
    		}
    	
    	}
    }

    public function Logout()
    {
    	Session::delete('username');
    	Session::delete('password');
    	Session::delete('group');
    
    	Session::clear();
    	$this->success('退出成功', '/admin/Login/index/');
    
    }
    
}
