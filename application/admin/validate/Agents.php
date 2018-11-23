<?php

namespace app\admin\validate;

use think\Validate;


class Agents extends Validate
{
	protected $rule = [
		'phone'             => 'require|length:11|number|unique:agents',
		'nickname'          => 'max:60',
		'password|密码'     => 'length:6,16|alphaDash',
		'repassword'        => 'confirm:password',
		'wechat'            => 'alphaDash|length:6,20|unique:agents',
		'name'              => 'max:30',
		'sex'               => 'in:m,w,s',
		'id_card'           => 'length:18|alphaNum',
		// 'province'          => '',
		// 'city'              => '',
		// 'area'              => '',
		'address'           => 'max:150',
		'generation'        => 'number',
		// 'role'              => '',
		'inviter'           => 'number',
		'financial_type'    => 'number',
		// 'financial_account' => '',
		// 'end_time'          => '',
		'__token__'         => 'token',
	];

	protected $message = [
		'phone.length'       => '登录账号为11位手机号',
		'phone.require'      => '登录账号必须填写',
		'phone.number'       => '登录账号为11位手机号数字',
		'phone.unique'       => '该账号已存在不能再次添加',
		'nickname.max'       => '昵称最长20个字符',
		'password.length'    => '密码在6-16位之间',
		'password.alphaDash' => '密码只能包含字母、数字、下划线和减号',
		'repassword.confirm' => '两次输入密码不一致',
		'wechat.alphaDash'   => '微信号只能包含字母、数字、下划线和减号',
		'wechat.length'      => '微信号长度为6—20位',
		'wechat.unique'      => '该微信号已存在',
		'name.max'           => '姓名最长10个字符',
		'id_card.length'     => '身份证号码长度为18位',
		'id_card.alphaNum'   => '身份证号码只包含数字和字母',
		'address.max'        => '详细地址长度不能超过50个字符',
		'generation.number'  => '请选择合法代数',
		'inviter.number'     => '邀请人非法参数',
		'financial_type'     => '账号类型错误'
	];

	protected $scene = [
		'add' => ['phone','password','repassword','nickname','wechat','name','sex','id_card','address','generation','inviter','financial_type',],
		'edit' => ['phone'],
	];
}

?>