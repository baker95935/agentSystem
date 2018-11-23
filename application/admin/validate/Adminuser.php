<?php 

namespace app\admin\validate;

use think\Validate;


class Adminuser extends Validate
{
	protected $rule = [
		'username'  => 'require|max:30',
		'password' => 'require|length:6,16',
		'realname'=>'require',
		'email' => 'email',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'username.require' => '用户名必须填写',
		'username.max'     => '名称最多不能超过30个字符',
		'password.require' => '密码必须填写',
		'realname.require' => '真实姓名必须填写',
		'password.length'  => '密码在6位和16之间',
		'email'        => '邮箱格式错误',
	];
	
	
	protected $scene = [
		'edit'  =>  ['username','email'],
	];
}

?>