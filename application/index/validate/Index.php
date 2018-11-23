<?php

namespace app\index\validate;

use think\Validate;


class Index extends Validate
{
	protected $rule = [
		'phone'     => 'require|length:11|number|unique:agents',
		'wechat'    => 'alphaDash|length:6,20',
		'__token__' => 'token',
	];

	protected $message = [
		'phone.length'     => '登录账号为11位手机号',
		'phone.require'    => '登录账号必须填写',
		'phone.number'     => '登录账号为11位手机号数字',
		'phone.unique'     => '该账号已存在不能再次添加',
		'wechat.alphaDash' => '微信号只能包含字母、数字、下划线和减号',
		'wechat.length'    => '微信号长度为6—20位',
	];

	protected $scene = [
		'add' => ['phone','wechat'],
		'check' => ['phone'],
	];
}

?>