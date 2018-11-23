<?php 

namespace app\admin\validate;

use think\Validate;

class Login extends Validate
{
	protected $rule = [
		'username'  => 'require',
		'password' => 'require',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'username.require' => '用户名必须填写',
		'password.require' => '密码必须填写',
	];
}

?>