<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Adminuser extends Model
{
    protected $table = 'admin_user';
    
    //验证登录
    public function validLogin($data)
    {
    	//逻辑思路处理，根据用户名找密码
    	$result=0;
    
    	if(!empty($data['username']) && !empty($data['password']))
    	{
    		$dataInfo=Adminuser::where('username','=',$data['username'])->find();
    		if(!empty($dataInfo) && $data['password']==$dataInfo->password)
    		{
    			Session::set('userid',$dataInfo->id);
    			Session::set('username',$dataInfo->username);
    			Session::set('password',md5($dataInfo->id.$dataInfo->password));
    			Session::set('group',$dataInfo->group);
    			$result=1;
    		}
    	}
    
    	return $result;
    
    }
}
